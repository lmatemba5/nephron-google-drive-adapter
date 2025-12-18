<?php

namespace Nephron\Mutators;

use Nephron\Adapters\GoogleDriveAdapter;
use Nephron\Enums\StreamMode;

class Getter
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive,
    ) {
    }

    public function get(string $fileId, StreamMode $mode = StreamMode::INLINE)
    {
        return $this->googleDrive->get($fileId, $mode);
    }

    public function find($fileName, $parentId=null, $perPage=null, $pageToken=null)
    {
        return $this->googleDrive->find($fileName, $parentId, $perPage, $pageToken);
    }
    
    public function listFiles($parentId=null, $perPage=null, $pageToken=null){
        return $this->googleDrive->listFiles($parentId, $perPage, $pageToken);
    }
}