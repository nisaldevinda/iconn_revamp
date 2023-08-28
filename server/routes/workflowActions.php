<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowAction', "WorkflowActionController@store");
    $router->get('/workflowAction', "WorkflowActionController@index");
    $router->get('/workflowContextBaseAction', "WorkflowActionController@getWorkflowContextBaseAction");
    $router->get('/workflowAction/{id}', "WorkflowActionController@showById");
    $router->get('/workflowAction/{keyword}/keyword', "WorkflowActionController@showByKeyword");
    $router->put('/workflowAction/{id}', "WorkflowActionController@update");
    $router->delete('/workflowAction/{id}', "WorkflowActionController@delete");
    $router->get('/accessible-workflow-actions/{workflowId}/workflow/{employeeId}/employee/{instanceId}', "WorkflowActionController@accessibleWorkflowActions");
});
