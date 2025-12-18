<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;
use Illuminate\Http\UploadedFile;

/**
 * @internal
 * @psalm-internal Nephron
 */
class Uploader
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {}

    public function put(UploadedFile $file, ?string $folderId = null, ?string $fileName = null, $isPublic = false)
    {
        return $this->googleDrive->put($file, $folderId, $fileName, $isPublic);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->googleDrive->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->googleDrive->makeFilePrivate($fileId);
    }

    public function rename(string $fileId, string $newName)
    {
        return $this->googleDrive->rename($fileId, $newName);
    }
}
