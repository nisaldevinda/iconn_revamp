<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowDefine', "WorkflowDefineController@store");
    $router->get('/workflowDefine', "WorkflowDefineController@index");
    $router->get('/workflowDefine/{id}', "WorkflowDefineController@showById");
    $router->get('/workflowDefine/{keyword}/keyword', "WorkflowDefineController@showByKeyword");
    $router->put('/workflowDefine/{id}', "WorkflowDefineController@update");
    $router->delete('/workflowDefine/{id}', "WorkflowDefineController@delete");
    $router->put('/workflow-builder/update-workflow-procedure-type/{id}', "WorkflowDefineController@updateWorkflowProcedureType");
    $router->post('/workflow-builder/add-workflow-approver-level', "WorkflowDefineController@addWorkflowApproverLevel");
    $router->put('/workflow-builder/update-workflow-level-configurations/{id}', "WorkflowDefineController@updateWorkflowLevelConfigurations");
    $router->delete('/workflow-builder/delete-workflow-approver-level/{id}', "WorkflowDefineController@deleteWorkflowLevelConfigurations");
});
