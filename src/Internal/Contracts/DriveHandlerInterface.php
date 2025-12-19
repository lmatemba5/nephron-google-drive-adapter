<?php

namespace Nephron\Internal\Contracts;

use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Nephron\Enums\StreamMode;
use Nephron\Models\PaginatedDriveFiles;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This is an internal implementation and it can change anytime. Do not use it directly
 * 
 * Use Nephron\GoogleDrive instead.
 * 
 * @internal
 * @psalm-internal Nephron
 * @phpstan-internal Nephron
 */
interface DriveHandlerInterface
{
    public function put(
       UploadedFile $file, 
       ?string $folderId, 
       ?string $fileName, 
       bool $strict, 
       bool $isPublic
    ): DriveFile;

    public function mkdir(
        string $directoryName, 
        ?string $folderId, 
        bool $strict, 
        bool $isPublic
    ): DriveFile;

    public function find(
        string $fileName,
        ?string $parentId,
        ?int $perPage,
        ?string $pageToken
    ): PaginatedDriveFiles;

    public function makeFilePublic(
        string $fileId
    ): bool;

    public function makeFilePrivate(
        string $fileId
    ): bool;

    public function listFiles(
        ?string $parentId,
        ?int $perPage,
        ?string $pageToken
    ): PaginatedDriveFiles;

    public function rename(
        string $fileId, 
        string $newName, 
        ?string $parentFolderId, 
        bool $strict
    ): DriveFile;

    public function get(
        string $fileId,
        StreamMode $mode
    ): StreamedResponse;

    public function delete(
        string $fileId
    ): bool;
}