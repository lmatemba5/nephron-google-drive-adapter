<?php 

namespace Nephron\Models;
use Google\Service\Drive\DriveFile;

final class PaginatedDriveFiles
{
    /** @param DriveFile[] $data */
    public function __construct(
        public array $data,
        public ?string $nextPageToken
    ) {}
}
