<?php

namespace Nephron\Adapters;

use Nephron\Enums\StreamMode;
use Nephron\Mutators\Uploader;
use Nephron\Mutators\Deleter;
use Nephron\Mutators\DirectoryManager;
use Nephron\Mutators\Getter;
use Illuminate\Http\UploadedFile;

class GoogleDriveHandler
{
    public function __construct(
        private readonly Uploader $uploader,
        private readonly Getter $getter,
        private readonly Deleter $deleter,
        private readonly DirectoryManager $dmanager
    ) {
    }

    public function put(UploadedFile $file, string $folderId, $isPublic=false)
    {
        return $this->uploader->put($file, $folderId, $isPublic);
    }

    public function mkdir($directoryName, $folderId = null, $isPublic=false)
    {
        return $this->dmanager->mkdir($directoryName, $folderId, $isPublic);
    }

    public function find($fileName, $parentId=null, $perPage=null, $pageToken=null)
    {
        return $this->getter->find($fileName, $parentId, $perPage, $pageToken);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->uploader->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->uploader->makeFilePrivate($fileId);
    }
    
    public function listFiles($parentId=null, $perPage= null, $pageToken=null){
        return $this->getter->listFiles($parentId, $perPage, $pageToken);
    }

    public function rename($fileId, $newName)
    {
        return $this->uploader->rename($fileId, $newName);
    }

    public function get(string $fileId, StreamMode $mode)
    {
        return $this->getter->get($fileId, $mode);
    }

    public function delete(string $fileId): bool
    {
        return $this->deleter->delete($fileId);
    }
}