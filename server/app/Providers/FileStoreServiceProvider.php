<?php

namespace App\Providers;

use App\Library\FileStore;
use App\Library\Session;
use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider;

class FileStoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FileStore::class, function ($app) {
            $s3Client = new S3Client([
                'version' => config('fileStorage.version'),
                'region' => config('fileStorage.region'),
                'endpoint' => config('fileStorage.endpoint'),
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => config('fileStorage.key'),
                    'secret' => config('fileStorage.secret'),
                ],
            ]);
            $session = app(Session::class);

            return new FileStore($s3Client, $session);
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
    }
}
