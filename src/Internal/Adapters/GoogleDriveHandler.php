<?php

namespace Nephron\Internal\Adapters;

use Google\Service\Drive\DriveFile;
use Nephron\Enums\StreamMode;
use Nephron\Internal\Mutators\Uploader;
use Nephron\Internal\Mutators\Deleter;
use Nephron\Internal\Mutators\DirectoryManager;
use Nephron\Internal\Mutators\Getter;
use Illuminate\Http\UploadedFile;
use Nephron\Models\PaginatedDriveFiles;

class GoogleDriveHandler
{
    public function __construct(
        private readonly Uploader $uploader,
        private readonly Getter $getter,
        private readonly Deleter $deleter,
        private readonly DirectoryManager $dmanager
    ) {}

    public function put(UploadedFile $file, ?string $folderId = null, ?string $fileName = null, $isPublic = false)
    {
        return $this->uploader->put($file, $folderId, $fileName, $isPublic);
    }

    public function mkdir(string $directoryName, ?string $folderId = null, $isPublic = false): DriveFile
    {
        return $this->dmanager->mkdir($directoryName, $folderId, $isPublic);
    }

    public function find(string $fileName, ?string $parentId = null, ?int $perPage = null, ?string $pageToken = null): PaginatedDriveFiles
    {
        return $this->getter->find($fileName, $parentId, $perPage, $pageToken);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->uploader->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->uploader->makeFilePrivate($fileId);
    }

    public function listFiles(?string $parentId = null, ?int $perPage = null, ?string $pageToken = null): PaginatedDriveFiles
    {
        return $this->getter->listFiles($parentId, $perPage, $pageToken);
    }

    public function rename(string $fileId, string $newName)
    {
        return $this->uploader->rename($fileId, $newName);
    }

    public function get(string $fileId, StreamMode $mode)
    {
        return $this->getter->get($fileId, $mode);
    }

    public function delete(string $fileId)
    {
        return $this->deleter->delete($fileId);
    }
}
