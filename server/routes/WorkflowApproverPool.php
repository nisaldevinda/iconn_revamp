<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api','middleware' => ['jwt']], function () use ($router) {
    $router->get('/workflowApproverPools', "WorkflowApproverPoolsController@getAllWorkflowApproverPools");
    $router->post('/workflowApproverPools', "WorkflowApproverPoolsController@createWorkflowApproverPool");
    $router->put('/workflowApproverPools/{id}', "WorkflowApproverPoolsController@updateWorkflowApproverPool");
    $router->delete('/workflowApproverPools/{id}', "WorkflowApproverPoolsController@deleteWorkflowApproverPool");
    
});