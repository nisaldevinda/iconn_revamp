<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowState', "WorkflowStateController@store");
    $router->get('/workflowState', "WorkflowStateController@index");
    $router->get('/workflowState/{id}', "WorkflowStateController@showById");
    $router->get('/workflowState/{keyword}/keyword', "WorkflowStateController@showByKeyword");
    $router->put('/workflowState/{id}', "WorkflowStateController@update");
    $router->delete('/workflowState/{id}', "WorkflowStateController@delete");
});
