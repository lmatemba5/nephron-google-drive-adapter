<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;

/**
 * @internal
 * @psalm-internal Nephron
 */
class Deleter
{
    public function __construct(private readonly GoogleDriveAdapter $googleDrive)
    {}

    public function delete(string $fileId): bool
    {
        return $this->googleDrive->delete($fileId);
    }
}
