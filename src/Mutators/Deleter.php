<?php

namespace Nephron\Mutators;

use Nephron\Adapters\GoogleDriveAdapter;

class Deleter
{
    public function __construct(private readonly GoogleDriveAdapter $googleDrive)
    {}

    public function delete(string $fileId): bool
    {
        return $this->googleDrive->delete($fileId);
    }
}
