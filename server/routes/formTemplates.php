<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to notice controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/form-templates/{id}', "FormTemplateController@getFormTemplate");
    $router->get('/form-templates', "FormTemplateController@getAllFormTemplates");
    $router->post('/form-templates', "FormTemplateController@createFormTemplate");
    $router->put('/form-templates/{id}', "FormTemplateController@updateFormTemplate");
    $router->delete('/form-templates/{id}', "FormTemplateController@deleteFormTemplate");
    $router->put('/update-form-template-status', "FormTemplateController@updateFormTemplateStatus");
    $router->get('/form-templates/{instanceHash}/instance', "FormTemplateController@getFormTemplateInstance");
    $router->put('/form-templates/{id}/instance', "FormTemplateController@updateFormTemplateInstance");
    $router->get('/form-templates/{id}/job-instances', "FormTemplateController@getFormTemplateJobInstances");
});
