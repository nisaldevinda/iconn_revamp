<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/qualification-levels', "QualificationLevelController@store");
    $router->get('/qualification-levels', "QualificationLevelController@index");
    $router->get('/qualification-levels/{id}', "QualificationLevelController@showById");
    $router->get('/qualification-levels-raw', "QualificationLevelController@getAllRawqualificationLevels");
    $router->get('/qualification-levels/{keyword}/keyword', "QualificationLevelController@showByKeyword");
    $router->put('/qualification-levels/{id}', "QualificationLevelController@update");
    $router->delete('/qualification-levels/{id}', "QualificationLevelController@delete");
});
