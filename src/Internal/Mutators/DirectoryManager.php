<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;

/**
 * @internal
 * @psalm-internal Nephron
 */
class DirectoryManager
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {
    }

    public function mkdir(string $directoryName, ?string $folderId = null, $isPublic=false)
    {
        return $this->googleDrive->mkdir($directoryName, $folderId, $isPublic);
    }
}