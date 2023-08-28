<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can competency-type all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/competency-types', "CompetencyTypeController@store");
    $router->get('/competency-types', "CompetencyTypeController@getAllCompetencyTypes");
    $router->get('/competency-types/{id}', "CompetencyTypeController@show");
    $router->get('/competency-types/{keyword}/keyword', "CompetencyTypeController@showByKeyword");
    $router->put('/competency-types/{id}', "CompetencyTypeController@update");
    $router->delete('/competency-types/{id}', "CompetencyTypeController@delete");
});
