<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;
use Illuminate\Http\UploadedFile;

/**
 * This is an internal implementation and it can change anytime. Do not use it directly
 * 
 * Use Nephron\GoogleDrive instead.
 * 
 * @internal
 * @psalm-internal Nephron
 * @phpstan-internal Nephron
 */

class Uploader
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {}

    public function put(UploadedFile $file, ?string $folderId, ?string $fileName, bool $strict, bool $isPublic)
    {
        return $this->googleDrive->put($file, $folderId, $fileName, $strict, $isPublic);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->googleDrive->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->googleDrive->makeFilePrivate($fileId);
    }

    public function rename(string $fileId, string $newName, ?string $parentFolderId, bool $strict)
    {
        return $this->googleDrive->rename($fileId, $newName, $parentFolderId, $strict);
    }
}
