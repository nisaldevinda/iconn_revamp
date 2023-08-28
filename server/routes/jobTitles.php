<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/job-titles', "JobTitleController@store");
    $router->get('/job-titles', "JobTitleController@index");
    $router->get('/job-titles/{id}', "JobTitleController@show");
    $router->get('/job-titles/{keyword}/keyword', "JobTitleController@showByKeyword");
    $router->put('/job-titles/{id}', "JobTitleController@update");
    $router->delete('/job-titles/{id}', "JobTitleController@delete");
});
