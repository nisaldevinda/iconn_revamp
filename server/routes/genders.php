<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/genders', "GenderController@store");
    $router->get('/genders', "GenderController@index");
    $router->get('/genders/{id}', "GenderController@showById");
    $router->get('/genders/{keyword}/keyword', "GenderController@showByKeyword");
    $router->put('/genders/{id}', "GenderController@update");
    $router->delete('/genders/{id}', "GenderController@delete");
});
