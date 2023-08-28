<?php

namespace App\Providers;

use App\Library\ActiveDirectory;
use Illuminate\Support\ServiceProvider;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client;

class ActiveDirectoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ActiveDirectory::class, function ($app) {
            return new ActiveDirectory(
                new Client(),
                new Graph()
            );
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
