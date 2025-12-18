<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Nephron\Internal\Providers\GoogleDriveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GoogleDriveServiceProvider::class,
        ];
    }
}