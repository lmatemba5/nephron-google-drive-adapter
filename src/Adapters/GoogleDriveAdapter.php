<?php

namespace Nephron\Adapters;

use Google\Service\{Drive, Drive\DriveFile, Drive\Permission};
use Nephron\Enums\StreamMode;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Http\UploadedFile;;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveAdapter
{
    private $parentId;

    public function __construct(
        private readonly Drive $googleServiceDrive

    ) {
        $this->parentId = $this->parentId ?: config('credentials.folder_id');
    }

    public function put(UploadedFile $file, $folderId = null, $fileName = null, $isPublic = false): DriveFile
    {
        $filemetadata = new DriveFile([
            'name' => $fileName ?: $file->getClientOriginalName(), 
            'parents' => [
                $folderId ?: $this->parentId
            ]
        ]);

        $driveFile =  $this->googleServiceDrive->files->create($filemetadata, [
            'data' => $file->getContent(),
            'uploadType' => 'multipart',
            'fields' => 'id,name,webViewLink'
        ]);

        if ($isPublic) {
            $this->makeFilePublic($driveFile->id);
        }

        return $driveFile;
    }

    public function get(string $fileId, StreamMode $mode = StreamMode::INLINE): StreamedResponse
    {
        $metadata = $this->googleServiceDrive->files->get($fileId, [
            'fields' => 'name,mimeType'
        ]);

        /** @var ResponseInterface $mediaResponse */
        $mediaResponse = $this->googleServiceDrive->files->get($fileId, [
            'alt' => 'media',
        ]);

        $headers = $this->headers($metadata->mimeType,$metadata->name, $mode);

        return response()->stream(
            fn() => fpassthru($mediaResponse->getBody()->detach()),
            200,
            $headers
        );
    }

    public function delete(string $fileId): bool
    {
        $response = $this->googleServiceDrive->files->delete($fileId);

        return empty($response->getBody()->getContents());
    }

    public function mkdir(string $directoryName, $parentFolderId = null, $isPublic = false): DriveFile
    {
        $driveFolder = $this->googleServiceDrive->files->create(
            new DriveFile([
                'name' => $directoryName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId ?: $this->parentId]
            ]),
            [
                'fields' => 'id,name,webViewLink'
            ]
        );

        if ($isPublic) {
            $this->makeFilePublic($driveFolder->id);
        }

        return $driveFolder;
    }

    public function find($fileName, $parentId = null, $perPage = null, $pageToken = null)
    {
        return $this->search("name = '$fileName'", $parentId ?: $this->parentId, $perPage, $pageToken);
    }

    public function rename($fileId, $newName): DriveFile
    {
        $newFileAttr = new DriveFile();
        $newFileAttr->setName($newName);

        $updatedFile = $this->googleServiceDrive->files->update($fileId, $newFileAttr, ['fields' => 'id, name']);

        return $updatedFile;
    }

    public function listFiles($parentId = null, $perPage = null, $pageToken = null)
    {
        return $this->search("'" . $parentId . "' in parents", $parentId ?: $this->parentId, $perPage, $pageToken);
    }

    public function makeFilePublic($fileId)
    {
        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);

        try {
            $this->googleServiceDrive->permissions->create($fileId, $permission);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }

    public function makeFilePrivate($fileId): bool
    {
        try {
            $reponse = $this->googleServiceDrive->permissions->delete($fileId, 'anyoneWithLink');
            return $reponse->getStatusCode() == 204;
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }

    private function search($q, $parentId, $perPage = null, $pageToken = null)
    {
        $perPage = $perPage == null ? 10 : $perPage;
        $optParams = array(
            'spaces' => 'drive',
            'q' => $q,
            'pageSize' => $perPage,
            'pageToken' => $pageToken,
            'fields' => 'nextPageToken,files(id,name,webViewLink,parents,size)',
        );
        

        $files = $this->googleServiceDrive->files->listFiles($optParams);

        $result = [];

        foreach ($files->getFiles() as $file) {
            $isMyChild = false;

            foreach ($file->parents as $parent) {
                if ($parent == $parentId) {
                    $isMyChild = true;
                    break;
                }
            }

            if ($isMyChild) {
                $result[] = $file;
            }
        }

        return [
            'data' => $result,
            'nextPageToken' => $files->getNextPageToken()
        ];
    }

    private function headers(
        string $mime,
        string $filename,
        StreamMode $mode
    ): array {
        $headers = [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];

        return match ($mode) {
            StreamMode::INLINE => $this->inline($mime, $headers),
            StreamMode::DOWNLOAD => $this->download($filename, $headers),
        };
    }

    private function inline(string $mime, array $headers): array
    {
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'video/mp4'])) {
            abort(403, 'Inline preview not allowed');
        }

        return array_merge($headers, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline',
            'Accept-Ranges'       => $mime === 'video/mp4' ? 'bytes' : 'none',
        ]);
    }

    private function download(
        string $filename,
        array $headers
    ): array {
        return array_merge($headers, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
