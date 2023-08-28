<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to resignation types controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/resignation-types', "ResignationTypeController@store");
    $router->get('/resignation-types', "ResignationTypeController@index");
    $router->get('/resignation-types/{id}', "ResignationTypeController@showById");
    $router->get('/resignation-types/{keyword}/keyword', "ResignationTypeController@showByKeyword");
    $router->put('/resignation-types/{id}', "ResignationTypeController@update");
    $router->delete('/resignation-types/{id}', "ResignationTypeController@delete");
});
