<?php

namespace Nephron;

use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Nephron\Enums\StreamMode;
use Nephron\Internal\Adapters\GoogleDriveHandler;
use Nephron\Models\PaginatedDriveFiles;

final class GoogleDrive
{
    public function __construct(
        private readonly GoogleDriveHandler $handler
    ) {}

    /**
     * Create a new file 
     * 
     * @param UploadedFile $file the file to be uploaded
     * @param string|null $folderId the parent container for this new file
     * @param bool $isPublic wheather to make it prvate or public
     * @return DriveFile
     */
    public function put(UploadedFile $file, ?string $folderId = null, ?string $fileName = null, bool $strict = true, bool $isPublic = false)
    {
        return $this->handler->put($file, $folderId, $fileName, $strict, $isPublic);
    }

    /**
     * Create a new folder 
     * 
     * @param string $directoryName the name of the new folder
     * @param string|null $folderId the parent container for this new folder
     * @param bool $isPublic wheather to make it prvate or public
     * @return DriveFile
     */
    public function mkdir(string $directoryName, ?string $folderId = null, bool $strict = true, bool $isPublic = false): DriveFile
    {
        return $this->handler->mkdir($directoryName, $folderId, $strict, $isPublic);
    }

    /**
     * Search a file by name (paginated)
     * 
     * @param string $fileName the name of the file to be searched
     * @param string|null $parentId the parent folder to search in
     * @param int|null $perPage the number of items to fetch per iteration
     * @param string|null $pageToken the auth token to be used for the second fetch
     * @return PaginatedDriveFiles{
     *     data: DriveFile[],
     *     key: string
     * }
     */
    public function find(string $fileName, ?string $parentId = null, int $perPage = 10, ?string $pageToken = null): PaginatedDriveFiles
    {
        return $this->handler->find($fileName, $parentId, $perPage, $pageToken);
    }

    /**
     * Make a file publicly available 
     * 
     * @param string $fileId the file's google drive id
     * @return bool
     */
    public function makeFilePublic(string $fileId)
    {
        return $this->handler->makeFilePublic($fileId);
    }

    /**
     * Make a file private 
     * 
     * @param string $fileId the file's google drive id
     * @return bool
     */
    public function makeFilePrivate(string $fileId)
    {
        return $this->handler->makeFilePrivate($fileId);
    }

    /**
     * List files in a given folder (paginated)
     * 
     * @param string|null $parentId the parent folder to search in
     * @param int $perPage the number of items to fetch per iteration
     * @param string|null $pageToken the auth token to be used for the second fetch
     * @return PaginatedDriveFiles{
     *     data: DriveFile[],
     *     key: string
     * }
     */
    public function listFiles(?string $parentId = null, int $perPage = 10, ?string $pageToken = null): PaginatedDriveFiles
    {
        return $this->handler->listFiles($parentId, $perPage, $pageToken);
    }

    /**
     * Update the name of a file 
     * 
     * @param string $fileId the id for the file to be renamed
     * @param string $newName the new name of the file
     * @return DriveFile
     */
    public function rename(string $fileId, string $newName, ?string $parentFolderId = null, bool $strict = true)
    {
        return $this->handler->rename($fileId, $newName, $parentFolderId, $strict);
    }

    /**
     * Retrieve a file as a stream or download 
     * 
     * @param string $fileId the id for the file to stream
     * @param StreamMode $mode one of StreamMode::INLINE|StreamMode::DOWNLOAD
     * @return StreamedResponse
     */
    public function get(string $fileId, StreamMode $mode=StreamMode::INLINE)
    {
        return $this->handler->get($fileId, $mode);
    }

    /**
     * Delete a file in google drive 
     * 
     * @param string $fileId the id for the file to be deleted
     * @return bool
     */
    public function delete(string $fileId)
    {
        return $this->handler->delete($fileId);
    }
}