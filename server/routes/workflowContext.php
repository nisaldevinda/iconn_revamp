<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowContext', "WorkflowContextController@store");
    $router->get('/workflowContext', "WorkflowContextController@index");
    $router->get('/workflowContext/{id}', "WorkflowContextController@showById");
    $router->get('/workflowContext/{keyword}/keyword', "WorkflowContextController@showByKeyword");
    $router->put('/workflowContext/{id}', "WorkflowContextController@update");
    $router->delete('/workflowContext/{id}', "WorkflowContextController@delete");
});
