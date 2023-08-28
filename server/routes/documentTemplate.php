<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/document-templates', "DocumentTemplateController@list");
    $router->get('/document-templates/{id}', "DocumentTemplateController@getById");
    $router->post('/document-templates', "DocumentTemplateController@store");
    $router->put('/document-templates/{id}', "DocumentTemplateController@update");
    $router->delete('/document-templates/{id}', "DocumentTemplateController@delete");
    $router->post('/document-templates/category', "DocumentTemplateController@createCategory");
    $router->get('/document-templates-categories', "DocumentTemplateController@getAllDocumentCategories"); 
    $router->get('/document-templates-list/{id}' , "DocumentTemplateController@getDocumentTemplateList");
    $router->post('/document-templates/bulk-letter', "DocumentTemplateController@generateBulkLetter");
});
