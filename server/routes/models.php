<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to model controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/models/template-tokens', "ModelController@getTemplateTokens");
    $router->get('/models/workflow-template-tokens', "ModelController@getWorkflowRelateTemplateTokens");
    $router->get('/models', "ModelController@getAllModel");
    $router->get('/models/{modelName}', "ModelController@getModel");
    $router->get('/models/{modelName}/{alternative}', "ModelController@getModel");
});
