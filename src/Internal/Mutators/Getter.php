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
class Getter
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive,
    ) {}

    public function get(string $fileId, string $mode)
    {
        return $this->googleDrive->get($fileId, $mode);
    }

    public function find(string $fileName, ?string $parentId, ?int $perPage, ?string $pageToken)
    {
        return $this->googleDrive->find($fileName, $parentId, $perPage, $pageToken);
    }

    public function listFiles(?string $parentId, ?int $perPage, ?string $pageToken)
    {
        return $this->googleDrive->listFiles($parentId, $perPage, $pageToken);
    }
}
