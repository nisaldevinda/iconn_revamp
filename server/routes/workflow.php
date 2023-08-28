<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api','middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflow', "WorkflowController@store");
    $router->get('/allWorkflow/{id}', "WorkflowController@index");
    $router->get('/workflow', "WorkflowController@showById");
    $router->get('/workflow/{keyword}/keyword', "WorkflowController@showByKeyword");
    $router->get('/workflowFilter', "WorkflowController@showByfilter");
    $router->get('/leave-request-workflow-states', "WorkflowController@getLeaveRequestRelateWorkflowStates");
    $router->get('/short-leave-workflow-states', "WorkflowController@getShortLeaveRequestRelateWorkflowStates");
    $router->put('/workflow/{id}', "WorkflowController@update");
    $router->delete('/workflow/{id}', "WorkflowController@delete");
    $router->get('/workflow-config-tree/{id}', "WorkflowController@getWorkflowConfigTree");
    $router->get('/employee-pending-request-count' ,"WorkflowController@getPendingRequestCount");
    $router->get('/get-approval-level-wise-state/{id}',"WorkflowController@getApprovalLevelWiseStates");
});
