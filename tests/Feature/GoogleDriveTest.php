<?php

namespace Tests\Feature;

use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Nephron\GoogleDrive;
use Tests\TestCase;
use Mockery;
use Nephron\Enums\StreamMode;
use Nephron\Mutators\{Uploader, Getter, Deleter, DirectoryManager};
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeGoogleDrive(
        ?Uploader $uploader = null,
        ?Getter $getter = null,
        ?Deleter $deleter = null,
        ?DirectoryManager $dmanager = null
    ): GoogleDrive {
        return new GoogleDrive(
            $uploader ?? Mockery::mock(Uploader::class),
            $getter ?? Mockery::mock(Getter::class),
            $deleter ?? Mockery::mock(Deleter::class),
            $dmanager ?? Mockery::mock(DirectoryManager::class)
        );
    }

    public function test_can_put_file(): void
    {
        $file = UploadedFile::fake()->create('example.txt', 10);
        $folderId = 'test-folder';

        $uploaderMock = Mockery::mock(Uploader::class);
        $uploaderMock->shouldReceive('put')
            ->once()
            ->withArgs(fn($fileArg, $parentFolderIdArg) => $fileArg instanceof UploadedFile && $parentFolderIdArg === $folderId)
            ->andReturn((object)['id' => 'fake-file-id', 'name' => 'example.txt']);

        $result = $this->makeGoogleDrive($uploaderMock)->put($file, $folderId);

        $this->assertEquals('fake-file-id', $result->id);
        $this->assertEquals('example.txt', $result->name);
    }

    public function test_can_get_a_file_as_streamed_response(): void
    {
        $fileId = 'test-file-id';
        $streamMode = StreamMode::INLINE;

        $getterMock = Mockery::mock(Getter::class);
        $getterMock->shouldReceive('get')
            ->once()
            ->with($fileId, $streamMode)
            ->andReturn(new StreamedResponse(fn() => print('file contents'), 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'inline; filename="example.txt"',
            ]));

        $response = $this->makeGoogleDrive(null, $getterMock)->get($fileId, $streamMode);

        $this->assertInstanceOf(StreamedResponse::class, $response);

        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();

        $this->assertEquals('file contents', $contents);
    }

    public function test_can_delete_a_file(): void
    {
        $fileId = 'test-file-id';

        $deleterMock = Mockery::mock(Deleter::class);
        $deleterMock->shouldReceive('delete')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->makeGoogleDrive(null, null, $deleterMock)->delete($fileId));
    }

    public function test_can_return_false_when_delete_fails(): void
    {
        $fileId = 'nonexistent-file';

        $deleterMock = Mockery::mock(Deleter::class);
        $deleterMock->shouldReceive('delete')->once()->with($fileId)->andReturn(false);

        $this->assertFalse($this->makeGoogleDrive(null, null, $deleterMock)->delete($fileId));
    }

    public function test_can_create_a_directory(): void
    {
        $dirName = 'TestDir';
        $parentId = 'parent123';

        $dmanagerMock = Mockery::mock(DirectoryManager::class);
        $dmanagerMock->shouldReceive('mkdir')
            ->once()
            ->with($dirName, $parentId, false)
            ->andReturn(new DriveFile(['id' => 'dir123', 'name' => $dirName]));

        $result = $this->makeGoogleDrive(null, null, null, $dmanagerMock)->mkdir($dirName, $parentId);

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

        $getterMock = Mockery::mock(Getter::class);
        $getterMock->shouldReceive('find')
            ->once()
            ->with($fileName, $parentId, $perPage, $pageToken)
            ->andReturn([
                'data' => [
                    new DriveFile(['id' => 'file1', 'name' => 'example1.txt']),
                    new DriveFile(['id' => 'file2', 'name' => 'example2.txt']),
                ],
                'nextPageToken' => 'next123',
            ]);

        $result = $this->makeGoogleDrive(null, $getterMock)->find($fileName, $parentId, $perPage, $pageToken);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('nextPageToken', $result);
        $this->assertCount(2, $result['data']);
        $this->assertInstanceOf(DriveFile::class, $result['data'][0]);
    }

    public function test_can_rename_a_file(): void
    {
        $fileId = 'file123';
        $newName = 'newname.txt';

        $uploader = Mockery::mock(Uploader::class);
        $uploader->shouldReceive('rename')
            ->once()
            ->with($fileId, $newName)
            ->andReturn(new DriveFile(['id'=> $fileId, 'name'=> $newName]));

        $result = $this->makeGoogleDrive($uploader)->rename($fileId, $newName);

        $this->assertInstanceOf(DriveFile::class, $result);
        $this->assertEquals($newName, $result->name);
    }

    public function test_can_list_files_with_pagination(): void
    {
        $parentId = 'folder123';
        $perPage = 2;
        $pageToken = 'token123';

        $getterMock = Mockery::mock(Getter::class);
        $getterMock->shouldReceive('listFiles')
            ->once()
            ->with($parentId, $perPage, $pageToken)
            ->andReturn([
                'data' => [
                    new DriveFile(['id' => 'file1', 'name' => 'file1.txt']),
                    new DriveFile(['id' => 'file2', 'name' => 'file2.txt']),
                ],
                'nextPageToken' => 'next456',
            ]);

        $result = $this->makeGoogleDrive(null, $getterMock)->listFiles($parentId, $perPage, $pageToken);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('nextPageToken', $result);
        $this->assertCount(2, $result['data']);
        $this->assertInstanceOf(DriveFile::class, $result['data'][0]);
    }

    public function test_can_make_a_file_public(): void
    {
        $fileId = 'file123';

        $uploaderMock = Mockery::mock(Uploader::class);
        $uploaderMock->shouldReceive('makeFilePublic')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->makeGoogleDrive($uploaderMock)->makeFilePublic($fileId));
    }

    public function test_can_make_a_file_private(): void
    {
        $fileId = 'file123';

        $uploaderMock = Mockery::mock(Uploader::class);
        $uploaderMock->shouldReceive('makeFilePrivate')->once()->with($fileId)->andReturn(true);

        $this->assertTrue($this->makeGoogleDrive($uploaderMock)->makeFilePrivate($fileId));
    }
}