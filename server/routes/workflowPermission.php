<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowPermission', "WorkflowPermissionController@store");
    $router->get('/workflowPermission', "WorkflowPermissionController@index");
    $router->get('/workflowPermission/{id}', "WorkflowPermissionController@showById");
    $router->get('/workflowPermission/{keyword}/keyword', "WorkflowPermissionController@showByKeyword");
    $router->put('/workflowPermission/{id}', "WorkflowPermissionController@update");
    $router->delete('/workflowPermission/{id}', "WorkflowPermissionController@delete");
});
