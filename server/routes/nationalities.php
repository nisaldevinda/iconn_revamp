<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/nationalities', "NationalityController@store");
    $router->get('/nationalities', "NationalityController@index");
    $router->get('/nationalities/{id}', "NationalityController@showById");
    $router->get('/nationalities/{keyword}/keyword', "NationalityController@showByKeyword");
    $router->put('/nationalities/{id}', "NationalityController@update");
    $router->delete('/nationalities/{id}', "NationalityController@delete");
});
