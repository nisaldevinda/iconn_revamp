<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api','middleware' => ['jwt']], function () use ($router) {
    $router->post('/workflowStateTransition', "WorkflowStateTransitionController@store");
    $router->get('/workflowStateTransition', "WorkflowStateTransitionController@index");
    $router->get('/workflowStateTransition/{id}', "WorkflowStateTransitionController@showById");
    $router->get('/workflowStateTransition/{keyword}/keyword', "WorkflowStateTransitionController@showByKeyword");
    $router->put('/workflowStateTransition/{id}', "WorkflowStateTransitionController@update");
    $router->delete('/workflowStateTransition/{id}', "WorkflowStateTransitionController@delete");

});