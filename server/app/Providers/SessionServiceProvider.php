<?php

namespace App\Providers;

use App\Library\Context;
use App\Library\Permission;
use App\Library\Session;
use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Session::class, function () {
            return new Session(new Permission(), new Context());
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
