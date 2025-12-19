<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;

/**
 * This is an internal implementation and it can change anytime. Do not use it directly
 * 
 * Use Nephron\GoogleDrive instead.
 * 
 * @internal
 * @psalm-internal Nephron
 * @phpstan-internal Nephron
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