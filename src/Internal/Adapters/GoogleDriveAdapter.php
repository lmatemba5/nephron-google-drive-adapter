<?php
namespace Nephron\Internal\Adapters;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Nephron\Models\PaginatedDriveFiles;

/**
 * This is an internal implementation and it can change anytime. Do not use it directly
 *
 * Use Nephron\GoogleDrive instead.
 *
 * @internal
 * @psalm-internal Nephron
 * @phpstan-internal Nephron
 */
class GoogleDriveAdapter
{
    private $parentId;
    private $STREAMING_MODES = ['inline', 'download'];

    public function __construct(
        private readonly Drive $googleServiceDrive

    ) {
        $this->parentId = $this->parentId ?: config('credentials.folder_id');
    }

    public function put(UploadedFile $file, ?string $folderId, ?string $fileName, bool $strict, bool $isPublic): DriveFile
    {
        $parentId = $folderId ?: $this->parentId;

        $filemetadata = new DriveFile([
            'name'    => $fileName ?: $file->getClientOriginalName(),
            'parents' => [$parentId],
        ]);

        if ($strict) {
            $driveFile = $this->find($filemetadata->name, $parentId, null, null);

            if (count($driveFile->data) > 0) {
                return $driveFile->data[0];
            }
        }

        $driveFile = $this->googleServiceDrive->files->create($filemetadata, [
            'data'       => $file->getContent(),
            'uploadType' => 'multipart',
            'fields'     => 'id,name,webViewLink',
        ]);

        if ($isPublic) {
            $this->makeFilePublic($driveFile->id);
        }

        return $driveFile;
    }

    public function get(string $fileId, string $mode)
    {
        if (! in_array($mode, $this->STREAMING_MODES, true)) {
            throw new \InvalidArgumentException("Invalid streaming mode: {$mode}");
        }

        try {
            $metadata = $this->googleServiceDrive->files->get($fileId, [
                'fields' => 'name,mimeType,size',
            ]);
        } catch (\Google\Service\Exception $e) {
            abort($e->getCode() === 404 ? 404 : 403, 'File not accessible');
        }

        /** @var \Psr\Http\Message\ResponseInterface $mediaResponse */
        $mediaResponse = $this->googleServiceDrive->files->get($fileId, ['alt' => 'media']);

        $headers = $this->headers(
            $metadata->mimeType,
            $metadata->name,
            $mode,
            $mediaResponse->getHeaders()
        );

        return response(
            $mediaResponse->getBody()->getContents(),
            200,
            $headers
        );
    }

    public function delete(string $fileId): bool
    {
        $response = $this->googleServiceDrive->files->delete($fileId);

        return empty($response->getBody()->getContents());
    }

    public function mkdir(string $directoryName, ?string $parentFolderId, bool $strict, bool $isPublic): DriveFile
    {
        $parentId = $parentFolderId ?: $this->parentId;

        if ($strict) {
            $driveFile = $this->find($directoryName, $parentId, null, null);

            if (count($driveFile->data) > 0) {
                return $driveFile->data[0];
            }
        }

        $driveFolder = $this->googleServiceDrive->files->create(
            new DriveFile([
                'name'     => $directoryName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => [$parentFolderId ?: $this->parentId],
            ]),
            [
                'fields' => 'id,name,webViewLink',
            ]
        );

        if ($isPublic) {
            $this->makeFilePublic($driveFolder->id);
        }

        return $driveFolder;
    }

    public function find(string $fileName, ?string $parentId, ?int $perPage, ?string $pageToken): PaginatedDriveFiles
    {
        return $this->search("name = '" . $this->escapeQuery($fileName) . "'", $parentId ?: $this->parentId, $perPage, $pageToken);
    }

    public function escapeQuery(string $value): string
    {
        $escaped = str_replace('\\', '\\\\', $value);
        $escaped = str_replace("'", "\\'", $escaped);
        $escaped = str_replace('"', '\\"', $escaped);

        return $escaped;
    }

    public function rename(string $fileId, string $newName, ?string $parentFolderId, bool $strict): DriveFile
    {
        $newFileAttr = new DriveFile();
        $newFileAttr->setName($newName);

        $parentId = $parentFolderId ?: $this->parentId;

        if ($strict) {
            $driveFile = $this->find($newName, $parentId, null, null);

            if (count($driveFile->data) > 0) {
                abort(400, "The name is already taken.");
            }
        }

        $updatedFile = $this->googleServiceDrive->files->update($fileId, $newFileAttr, ['fields' => 'id, name']);

        return $updatedFile;
    }

    public function listFiles(?string $parentId, ?int $perPage, ?string $pageToken): PaginatedDriveFiles
    {
        $parentId = $parentId ?: $this->parentId;
        return $this->search("'" . $parentId . "' in parents", $parentId, $perPage, $pageToken);
    }

    public function makeFilePublic(string $fileId): bool
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

    private function search(string $q, string $parentId, ?int $perPage, ?string $pageToken): PaginatedDriveFiles
    {
        $optParams = [
            'spaces'    => 'drive',
            'q'         => $q,
            'pageSize'  => $perPage,
            'pageToken' => $pageToken,
            'fields'    => 'nextPageToken,files(id,name,webViewLink,parents,size)',
        ];

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
        string $mode,
        array $upstreamHeaders,
    ): array {
        $headers = [];

        if (isset($upstreamHeaders['Content-Length'][0])) {
            $headers['Content-Length'] = $upstreamHeaders['Content-Length'][0];
        }

        return match ($mode) {
            'inline'   => $this->inline($mime, $headers),
            'download' => $this->download($filename, $headers, $mime),
        };
    }

    private function inline(string $mime, array $headers): array
    {
        return array_merge($headers, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline',
        ]);
    }

    private function download(
        string $filename,
        array $headers,
        string $mime
    ): array {
        $extension = explode('/', $mime)[1];

        return array_merge($headers, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}.{$extension}\"",
        ]);
    }
}
