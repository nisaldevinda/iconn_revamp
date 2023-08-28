<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/resignation-processes', "ResignationProcessController@store");
    $router->get('/resignation-processes', "ResignationProcessController@index");
    $router->get('/resignation-processes/{id}', "ResignationProcessController@getById");
    $router->put('/resignation-processes/{id}', "ResignationProcessController@update");
    $router->delete('/resignation-processes/{id}', "ResignationProcessController@delete");
});