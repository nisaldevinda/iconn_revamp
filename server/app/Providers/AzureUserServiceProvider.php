<?php

namespace App\Providers;

use App\Library\AzureUser;
use Illuminate\Support\ServiceProvider;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client;

class AzureUserServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AzureUser::class, function ($app) {
            return new AzureUser();
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
