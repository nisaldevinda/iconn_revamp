<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/qualification-institutions', "QualificationInstitutionController@store");
    $router->get('/qualification-institutions', "QualificationInstitutionController@index");
    $router->get('/qualification-institutions/{id}', "QualificationInstitutionController@showById");
    $router->get('/qualification-institutions/{keyword}/keyword', "QualificationInstitutionController@showByKeyword");
    $router->put('/qualification-institutions/{id}', "QualificationInstitutionController@update");
    $router->delete('/qualification-institutions/{id}', "QualificationInstitutionController@delete");
});
