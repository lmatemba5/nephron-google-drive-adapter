<?php

namespace Tests\Feature;

use Nephron\GoogleDrive;
use Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_service_is_resolvable()
    {
        $service = $this->app->make(GoogleDrive::class);        
        $this->assertInstanceOf(GoogleDrive::class, $service);
    }
}
