<?php

namespace Nephron\Mutators;

use Nephron\Adapters\GoogleDriveAdapter;

class DirectoryManager
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {
    }

    public function mkdir($directoryName, $folderId = null, $isPublic=false)
    {
        return $this->googleDrive->mkdir($directoryName, $folderId, $isPublic);
    }
}