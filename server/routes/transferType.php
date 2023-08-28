<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to transfer type controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/transfer-types', "TransferTypeController@store");
    $router->get('/transfer-types', "TransferTypeController@index");
    $router->get('/transfer-types/{id}', "TransferTypeController@showById");
    $router->get('/transfer-types/{keyword}/keyword', "TransferTypeController@showByKeyword");
    $router->put('/transfer-types/{id}', "TransferTypeController@update");
    $router->delete('/transfer-types/{id}', "TransferTypeController@delete");
});
