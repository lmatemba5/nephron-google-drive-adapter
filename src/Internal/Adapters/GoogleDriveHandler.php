<?php

namespace Nephron\Internal\Adapters;

use Google\Service\Drive\DriveFile;
use Nephron\Internal\Mutators\Uploader;
use Nephron\Internal\Mutators\Deleter;
use Nephron\Internal\Mutators\DirectoryManager;
use Nephron\Internal\Mutators\Getter;
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
class GoogleDriveHandler
{
    public function __construct(
        private readonly Uploader $uploader,
        private readonly Getter $getter,
        private readonly Deleter $deleter,
        private readonly DirectoryManager $dmanager
    ) {}

    public function put(UploadedFile $file, ?string $folderId, ?string $fileName, bool $strict, bool $isPublic)
    {
        return $this->uploader->put($file, $folderId, $fileName, $strict, $isPublic);
    }

    public function mkdir(string $directoryName, ?string $folderId, bool $strict, bool $isPublic): DriveFile
    {
        return $this->dmanager->mkdir($directoryName, $folderId, $strict, $isPublic);
    }

    public function find(string $fileName, ?string $parentId, ?int $perPage, ?string $pageToken): PaginatedDriveFiles
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

    public function listFiles(?string $parentId, ?int $perPage, ?string $pageToken): PaginatedDriveFiles
    {
        return $this->getter->listFiles($parentId, $perPage, $pageToken);
    }

    public function rename(string $fileId, string $newName, ?string $parentFolderId, bool $strict)
    {
        return $this->uploader->rename($fileId, $newName, $parentFolderId, $strict);
    }

    public function get(string $fileId, string $mode)
    {
        return $this->getter->get($fileId, $mode);
    }

    public function delete(string $fileId)
    {
        return $this->deleter->delete($fileId);
    }
}
