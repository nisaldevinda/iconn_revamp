<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to scheme controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/schemes', "SchemeController@store");
    $router->get('/schemes', "SchemeController@index");
    $router->get('/schemes/{id}', "SchemeController@showById");
    $router->get('/schemes/{keyword}/keyword', "SchemeController@showByKeyword");
    $router->put('/schemes/{id}', "SchemeController@update");
    $router->delete('/schemes/{id}', "SchemeController@delete");
});
