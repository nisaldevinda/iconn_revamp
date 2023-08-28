<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/relationships', "RelationshipController@store");
    $router->get('/relationships', "RelationshipController@index");
    $router->get('/relationships/{id}', "RelationshipController@showById");
    $router->get('/relationships/{keyword}/keyword', "RelationshipController@showByKeyword");
    $router->put('/relationships/{id}', "RelationshipController@update");
    $router->delete('/relationships/{id}', "RelationshipController@delete");
});
