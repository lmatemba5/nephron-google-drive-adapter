<?php

namespace Nephron\Internal\Mutators;

use Nephron\Internal\Adapters\GoogleDriveAdapter;
use Nephron\Enums\StreamMode;

/**
 * @internal
 * @psalm-internal Nephron
 */
class Getter
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive,
    ) {}

    public function get(string $fileId, StreamMode $mode = StreamMode::INLINE)
    {
        return $this->googleDrive->get($fileId, $mode);
    }

    public function find(string $fileName, ?string $parentId  = null, ?int $perPage = null, $pageToken)
    {
        return $this->googleDrive->find($fileName, $parentId, $perPage, $pageToken);
    }

    public function listFiles(?string $parentId = null, ?int $perPage = null, ?string $pageToken = null)
    {
        return $this->googleDrive->listFiles($parentId, $perPage, $pageToken);
    }
}
