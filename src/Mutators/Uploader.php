<?php

namespace Nephron\Mutators;

use Nephron\Adapters\GoogleDriveAdapter;
use Illuminate\Http\UploadedFile;

class Uploader
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {}

    public function put(UploadedFile $file, string $folderId, $isPublic = false)
    {
        return $this->googleDrive->put($file, $folderId, $isPublic);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->googleDrive->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->googleDrive->makeFilePrivate($fileId);
    }

    public function rename($fileId, $newName)
    {
        return $this->googleDrive->rename($fileId, $newName);
    }
}
