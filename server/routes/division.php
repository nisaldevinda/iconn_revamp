<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to division controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/divisions', "DivisionController@getAllDivisions");
    $router->get('/divisions/{id}', "DivisionController@getDivision");
    $router->post('/divisions', "DivisionController@createDivision");
    $router->put('/divisions/{id}', "DivisionController@updateDivision");
    $router->delete('/divisions/{id}', "DivisionController@deleteDivision");
});
