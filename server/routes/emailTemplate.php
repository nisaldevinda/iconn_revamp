<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/email-templates', "EmailTemplateController@list");
    $router->get('/email-template-contents', "EmailTemplateController@listContent");
    $router->get('/email-template-contents-by-context-id', "EmailTemplateController@listContentByContextId");
    $router->get('/email-templates-tree-data', "EmailTemplateController@getEmailTemplateRelateTreeData");
    $router->get('/email-templates/{id}', "EmailTemplateController@getById");
    $router->get('/email-templates-contents/{id}', "EmailTemplateController@getContentById");
    $router->post('/email-templates', "EmailTemplateController@store");
    $router->put('/email-templates/{id}', "EmailTemplateController@update");
    $router->delete('/email-templates/{id}', "EmailTemplateController@delete");
});
