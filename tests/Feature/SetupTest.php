<?php

namespace Tests\Feature;

use Nephron\Enums\StreamMode;
use Nephron\GoogleDrive;
use Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_stream_mode_enum_exists()
    {
        $this->assertSame('inline', StreamMode::INLINE->value);
        $this->assertSame('download', StreamMode::DOWNLOAD->value);
    }

    public function test_service_is_resolvable()
    {
        $service = $this->app->make(GoogleDrive::class);        
        $this->assertInstanceOf(GoogleDrive::class, $service);
    }
}
