<?php

namespace Nephron\Internal\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Nephron\Internal\Adapters\{GoogleDriveAdapter, GoogleDriveHandler};
use Nephron\GoogleDrive;
use Nephron\Internal\Mutators\Deleter;
use Nephron\Internal\Mutators\DirectoryManager;
use Nephron\Internal\Mutators\Getter;
use Nephron\Internal\Mutators\Uploader;

/**
 * This is an internal implementation and it can change anytime. Do not use it directly
 * 
 * Use Nephron\GoogleDrive instead.
 * 
 * @internal
 * @psalm-internal Nephron
 * @phpstan-internal Nephron
 */
class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../../config/credentials.php' => config_path(
                    'credentials.php'
                ),
            ],
            'config'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/credentials.php',
            'credentials'
        );
    }

    public function register(): void
    {
        $this->app->singleton(GoogleDriveHandler::class, function () {
            $credentials = $this->getCredentials();

            if (empty($credentials)) {
                throw new \Exception(
                    'Service account file not found'
                );
            }

            $client = new Client();
            $client->setAuthConfig($credentials);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');

            $drive = new Drive($client);
            $drive->servicePath = config('credentials.folder_id');

            $adapter = new GoogleDriveAdapter($drive);

            return new GoogleDriveHandler(
                new Uploader($adapter),
                new Getter($adapter),
                new Deleter($adapter),
                new DirectoryManager($adapter)
            );
        });

        $this->app->singleton(GoogleDrive::class);

        Storage::extend("google", function ($app, $config) {
            return $app->make(GoogleDrive::class);
        });
    }

    /**
     * @return array<int,string>
     */
    public function provides(): array
    {
        return [
            GoogleDrive::class
        ];
    }

    /**
     * @return array<string,string>
     */
    private function getCredentials(): array
    {
        $credentialsFilePath = config('credentials.service_account_json');

        if (empty($credentialsFilePath)) {
            throw new \Exception(
                'Service_account file not found. Please check the GOOGLE_SERVICE_ACCOUNT_JSON_PATH .env variable.'
            );
        }

        $credentialsFileContent = file_get_contents($credentialsFilePath);

        return json_decode($credentialsFileContent ?: '', true) ?: [];
    }
}
