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
class Deleter
{
    public function __construct(private readonly GoogleDriveAdapter $googleDrive)
    {}

    public function delete(string $fileId): bool
    {
        return $this->googleDrive->delete($fileId);
    }
}
