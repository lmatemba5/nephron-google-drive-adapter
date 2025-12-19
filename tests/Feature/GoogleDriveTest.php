<?php

namespace Tests\Feature;

use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Mockery;
use Nephron\Enums\StreamMode;
use Nephron\GoogleDrive;
use Nephron\Internal\Adapters\GoogleDriveHandler;
use Nephron\Models\PaginatedDriveFiles;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class GoogleDriveTest extends TestCase
{
    /** @var \Mockery\MockInterface|GoogleDriveHandler $handlerMock */
    private $handlerMock;
    private GoogleDrive $drive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerMock = Mockery::mock(GoogleDriveHandler::class);
        $this->drive = new GoogleDrive($this->handlerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_put_file(): void
    {
        $file = UploadedFile::fake()->create('example.txt', 10);
        $folderId = 'test-folder';

        $this->handlerMock->shouldReceive('put')
            ->once()
            ->withArgs(fn($fileArg, $parentFolderIdArg) => $fileArg instanceof UploadedFile && $parentFolderIdArg === $folderId)
            ->andReturn((object)['id' => 'fake-file-id', 'name' => 'example.txt']);

        $result = $this->drive->put($file, $folderId);

        $this->assertEquals('fake-file-id', $result->id);
        $this->assertEquals('example.txt', $result->name);
    }

    public function test_can_get_a_file_as_streamed_response(): void
    {
        $fileId = 'test-file-id';
        $streammode = StreamMode::INLINE;

        $this->handlerMock->shouldReceive('get')
            ->once()
            ->with($fileId, $streammode)
            ->andReturn(new StreamedResponse(fn() => print('file contents'), 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'inline; filename="example.txt"',
            ]));

        $response = $this->drive->get($fileId, $streammode);

        $this->assertInstanceOf(StreamedResponse::class, $response);

        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();

        $this->assertEquals('file contents', $contents);
    }

    public function test_can_delete_a_file(): void
    {
        $fileId = 'test-file-id';

        $this->handlerMock->shouldReceive('delete')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->drive->delete($fileId));
    }

    public function test_can_return_false_when_delete_fails(): void
    {
        $fileId = 'nonexistent-file';

        $this->handlerMock->shouldReceive('delete')->once()->with($fileId)->andReturn(false);

        $this->assertFalse($this->drive->delete($fileId));
    }

    public function test_can_create_a_directory(): void
    {
        $dirName = 'TestDir';
        $parentId = 'parent123';

        $this->handlerMock->shouldReceive('mkdir')
            ->once()
            ->with($dirName, $parentId, true, false)
            ->andReturn(new DriveFile(['id' => 'dir123', 'name' => $dirName]));

        $result = $this->drive->mkdir($dirName, $parentId, true, false);

        $this->assertInstanceOf(DriveFile::class, $result);
        $this->assertEquals('dir123', $result->id);
        $this->assertEquals($dirName, $result->name);
    }

    public function test_can_find_files_with_pagination(): void
    {
        $fileName = 'example.txt';
        $parentId = 'folder123';
        $perPage = 2;
        $pageToken = 'token123';

        $this->handlerMock->shouldReceive('find')
            ->once()
            ->with($fileName, $parentId, $perPage, $pageToken)
            ->andReturn(new PaginatedDriveFiles(
                [
                    new DriveFile(['id' => 'file1', 'name' => 'example1.txt']),
                    new DriveFile(['id' => 'file2', 'name' => 'example2.txt']),
                ],
                'next123',
            ));

        $result = $this->drive->find($fileName, $parentId, $perPage, $pageToken);

        $this->assertObjectHasProperty("data", $result);
        $this->assertObjectHasProperty("nextPageToken", $result);
        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(DriveFile::class, $result->data[0]);
    }

    public function test_can_rename_a_file(): void
    {
        $fileId = 'file123';
        $newName = 'newname.txt';

        $this->handlerMock->shouldReceive('rename')
            ->once()
            ->with($fileId, $newName, null, true)
            ->andReturn(new DriveFile(['id' => $fileId, 'name' => $newName]));

        $result = $this->drive->rename($fileId, $newName, null, true);

        $this->assertInstanceOf(DriveFile::class, $result);
        $this->assertEquals($newName, $result->name);
    }

    public function test_can_list_files_with_pagination(): void
    {
        $parentId = 'folder123';
        $perPage = 2;
        $pageToken = 'token123';

        $this->handlerMock->shouldReceive('listFiles')
            ->once()
            ->with($parentId, $perPage, $pageToken)
            ->andReturn(new PaginatedDriveFiles(
                [
                    new DriveFile(['id' => 'file1', 'name' => 'file1.txt']),
                    new DriveFile(['id' => 'file2', 'name' => 'file2.txt']),
                ],
                'next456',
            ));

        $result = $this->drive->listFiles($parentId, $perPage, $pageToken);

        $this->assertObjectHasProperty("data", $result);
        $this->assertObjectHasProperty("nextPageToken", $result);
        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(DriveFile::class, $result->data[0]);
    }

    public function test_can_make_a_file_public(): void
    {
        $fileId = 'file123';

        $this->handlerMock->shouldReceive('makeFilePublic')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->drive->makeFilePublic($fileId));
    }

    public function test_can_make_a_file_private(): void
    {
        $fileId = 'file123';

        $this->handlerMock->shouldReceive('makeFilePrivate')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->drive->makeFilePrivate($fileId));
    }
}
