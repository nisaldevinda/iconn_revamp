<?php

namespace App\Providers;

use App\Library\Redis;
use App\Library\Session;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('redis', function () {
            $session = app(Session::class);
            return new Redis($session);
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
