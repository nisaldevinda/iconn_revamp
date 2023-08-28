<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/marital-status', "MaritalStatusController@store");
    $router->get('/marital-status', "MaritalStatusController@index");
    $router->get('/marital-status/{id}', "MaritalStatusController@showById");
    $router->get('/marital-status/{keyword}/keyword', "MaritalStatusController@showByKeyword");
    $router->put('/marital-status/{id}/', "MaritalStatusController@update");
    $router->delete('/marital-status/{id}/', "MaritalStatusController@delete");
});
