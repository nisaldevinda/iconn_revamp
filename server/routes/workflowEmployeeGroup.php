<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api','middleware' => ['jwt']], function () use ($router) {
    $router->get('/workflowEmployeeGroups', "WorkflowEmployeeGroupsController@getAllWorkflowEmployeeGroups");
    $router->post('/workflowEmployeeGroups', "WorkflowEmployeeGroupsController@createWorkflowEmployeeGroup");
    $router->put('/workflowEmployeeGroups/{id}', "WorkflowEmployeeGroupsController@updateWorkflowEmployeeGroup");
    $router->delete('/workflowEmployeeGroups/{id}', "WorkflowEmployeeGroupsController@deleteWorkflowEmployeeGroup");
    
});