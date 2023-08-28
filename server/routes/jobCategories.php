<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/job-categories', "JobCategoryController@store");
    $router->get('/job-categories', "JobCategoryController@index");
    $router->get('/job-categories/{id}', "JobCategoryController@show");
    $router->get('/job-categories/{keyword}/keyword', "JobCategoryController@showByKeyword");
    $router->put('/job-categories/{id}', "JobCategoryController@update");
    $router->delete('/job-categories/{id}', "JobCategoryController@delete");
});
