<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to religions controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/religions', "ReligionController@store");
    $router->get('/religions', "ReligionController@index");
    $router->get('/religions/{id}', "ReligionController@showById");
    $router->get('/religions/{keyword}/keyword', "ReligionController@showByKeyword");
    $router->put('/religions/{id}', "ReligionController@update");
    $router->delete('/religions/{id}', "ReligionController@delete");
});
