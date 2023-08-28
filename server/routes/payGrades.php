<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/payGrade', "PayGradesController@store");
    $router->get('/payGrade', "PayGradesController@index");
    $router->get('/payGrade/{id}', "PayGradesController@showById");
    $router->get('/payGrade/{keyword}/keyword', "PayGradesController@showByKeyword");
    $router->put('/payGrade/{id}', "PayGradesController@update");
    $router->delete('/payGrade/{id}', "PayGradesController@delete");
});
