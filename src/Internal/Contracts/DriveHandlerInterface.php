<?php

namespace Nephron\Internal\Contracts;

use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Nephron\Enums\StreamMode;
use Nephron\Models\PaginatedDriveFiles;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface DriveHandlerInterface
{
    public function put(
        UploadedFile $file,
        ?string $folderId = null,
        ?string $fileName = null,
        $isPublic = false
    ): DriveFile;

    public function mkdir(
        string $directoryName,
        ?string $folderId = null,
        $isPublic = false
    ): DriveFile;

    public function find(
        string $fileName,
        ?string $parentId = null,
        ?int $perPage = null,
        ?string $pageToken = null
    ): PaginatedDriveFiles;

    public function makeFilePublic(
        string $fileId
    ): bool;

    public function makeFilePrivate(
        string $fileId
    ): bool;

    public function listFiles(
        ?string $parentId = null,
        ?int $perPage = null,
        ?string $pageToken = null
    ): PaginatedDriveFiles;

    public function rename(
        string $fileId,
        string $newName
    ): DriveFile;

    public function get(
        string $fileId,
        StreamMode $mode
    ): StreamedResponse;

    public function delete(
        string $fileId
    ): bool;
}