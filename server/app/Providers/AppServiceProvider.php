<?php

namespace App\Providers;

use App\Library\JsonModelReader;
use App\Library\Interfaces\ModelReaderInterface;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(ModelReaderInterface::class, JsonModelReader::class);

        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('app.stripe_secret'));
        });

        // $container->bind(Database::class, function (Container $container) {
        //     return new MySQLDatabase(MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASS);
        // });

        // $this->app->bind(JsonModel::class, function ($app) {
        //     return new JsonModel('user');
        // });

        // $this->app->bind(JsonModelReader::class, function ($app) {
        //     return JsonModelReader(new ModelObject());
        // });



        // $this->app->bind(UserService::class, function ($app) {
        //     return new JsonModel('user');
        // });

        // $this->app->bind('App\Http\Controllers\UserController', function ($app) {
        //     return new HelpSpot\API($app->make('UserRepository'));
        // });
    }
}
