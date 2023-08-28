<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to confirmation reasons controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/confirmation-reasons', "ConfirmationReasonController@store");
    $router->get('/confirmation-reasons', "ConfirmationReasonController@index");
    $router->get('/confirmation-reasons/{id}', "ConfirmationReasonController@showById");
    $router->get('/confirmation-reasons/{keyword}/keyword', "ConfirmationReasonController@showByKeyword");
    $router->put('/confirmation-reasons/{id}', "ConfirmationReasonController@update");
    $router->delete('/confirmation-reasons/{id}', "ConfirmationReasonController@delete");
});
