<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/resignation-reasons', "TerminationReasonController@store");
    $router->get('/resignation-reasons', "TerminationReasonController@index");
    $router->get('/resignation-reasons/{id}', "TerminationReasonController@showById");
    $router->get('/resignation-reasons/{keyword}/keyword', "TerminationReasonController@showByKeyword");
    $router->put('/resignation-reasons/{id}', "TerminationReasonController@update");
    $router->delete('/resignation-reasons/{id}', "TerminationReasonController@delete");
});
