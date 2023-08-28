<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can competency-type all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/competency', "CompetencyController@store");
    $router->get('/competency', "CompetencyController@getAllCompetencies");
    $router->get('/competency/{id}', "CompetencyController@show");
    $router->get('/competency/{keyword}/keyword', "CompetencyController@showByKeyword");
    $router->put('/competency/{id}', "CompetencyController@update");
    $router->delete('/competency/{id}', "CompetencyController@delete");
});
