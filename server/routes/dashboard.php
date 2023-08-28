<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to dashboard controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->group(['middleware' => ['jwt']], function () use ($router) {
        $router->put('/dashboard', "DashboardController@updateDashboard");
        $router->get('/dashboard', "DashboardController@getDashboard");
    });
});
