<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to employment status controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/employment-status', "EmploymentStatusController@store");
    $router->get('/employment-status', "EmploymentStatusController@index");
    $router->get('/employment-status/{id}', "EmploymentStatusController@showById");
    $router->get('/employment-status/{keyword}/keyword', "EmploymentStatusController@showByKeyword");
    $router->put('/employment-status/{id}', "EmploymentStatusController@update");
    $router->delete('/employment-status/{id}', "EmploymentStatusController@delete");
});
