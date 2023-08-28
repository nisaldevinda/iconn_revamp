<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    if (config('app.env') == 'production') {
        return File::get(base_path() . '/public/index.html');
    }
    return $router->app->version();
});