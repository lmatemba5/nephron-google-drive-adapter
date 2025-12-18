<?php

namespace Nephron\Internal\Adapters;

use Google\Service\{Drive, Drive\DriveFile, Drive\Permission};
use Nephron\Enums\StreamMode;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Http\UploadedFile;
use Nephron\Models\PaginatedDriveFiles;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 * @psalm-internal Nephron
 */
class GoogleDriveAdapter
{
    private $parentId;

    public function __construct(
        private readonly Drive $googleServiceDrive

    ) {
        $this->parentId = $this->parentId ?: config('credentials.folder_id');
    }

    public function put(UploadedFile $file, ?string $folderId = null, ?string $fileName = null, $isPublic = false): DriveFile
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

    public function mkdir(string $directoryName, ?string $parentFolderId = null, $isPublic = false): DriveFile
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

    public function find(string $fileName, ?string $parentId = null, ?int $perPage = null, ?string $pageToken = null): PaginatedDriveFiles
    {
        return $this->search("name = '$fileName'", $parentId ?: $this->parentId, $perPage, $pageToken);
    }

    public function rename(string $fileId, string $newName): DriveFile
    {
        $newFileAttr = new DriveFile();
        $newFileAttr->setName($newName);

        $updatedFile = $this->googleServiceDrive->files->update($fileId, $newFileAttr, ['fields' => 'id, name']);

        return $updatedFile;
    }

    public function listFiles(?string $parentId = null, ?int $perPage = null, ?string $pageToken = null): PaginatedDriveFiles
    {
        $parentId = $parentId ?: $this->parentId;
        return $this->search("'" . $parentId . "' in parents", $parentId, $perPage, $pageToken);
    }

    public function makeFilePublic(string $fileId)
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

    public function makeFilePrivate(string $fileId): bool
    {
        try {
            $reponse = $this->googleServiceDrive->permissions->delete($fileId, 'anyoneWithLink');
            return $reponse->getStatusCode() == 204;
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }

    private function search(string $q, string $parentId, ?int $perPage = 10, ?string $pageToken = null): PaginatedDriveFiles
    {
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

        return new PaginatedDriveFiles($result, $files->getNextPageToken());
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