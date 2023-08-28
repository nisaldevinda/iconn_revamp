<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/salaryComponents', "SalaryComponentsController@store");
    $router->get('/salaryComponents', "SalaryComponentsController@index");
    $router->get('/salaryComponents/{id}', "SalaryComponentsController@showById");
    $router->get('/salaryComponents/{keyword}/keyword', "SalaryComponentsController@showByKeyword");
    $router->put('/salaryComponents/{id}', "SalaryComponentsController@update");
    $router->delete('/salaryComponents/{id}', "SalaryComponentsController@delete");
});
