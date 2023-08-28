<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/qualifications', "QualificationController@store");
    $router->get('/qualifications', "QualificationController@index");
    $router->get('/qualifications/{id}', "QualificationController@showById");
    $router->get('/qualifications/{keyword}/keyword', "QualificationController@showByKeyword");
    $router->put('/qualifications/{id}', "QualificationController@update");
    $router->delete('/qualifications/{id}', "QualificationController@delete");
});
