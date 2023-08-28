<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/confirmation-processes', "ConfirmationProcessController@store");
    $router->get('/confirmation-processes', "ConfirmationProcessController@index");
    $router->get('/confirmation-processes/{id}', "ConfirmationProcessController@getById");
    $router->put('/confirmation-processes/{id}', "ConfirmationProcessController@update");
    $router->delete('/confirmation-processes/{id}', "ConfirmationProcessController@delete");
});