<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to Race controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/races', "RaceController@store");
    $router->get('/races', "RaceController@index");
    $router->get('/races/{id}', "RaceController@showById");
    $router->get('/races/{keyword}/keyword', "RaceController@showByKeyword");
    $router->put('/races/{id}', "RaceController@update");
    $router->delete('/races/{id}', "RaceController@delete");
});
