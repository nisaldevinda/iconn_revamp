<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api/dynamic-forms', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/', "DynamicFormController@store");
    $router->get('/', "DynamicFormController@index");
    $router->get('/{id}', "DynamicFormController@showById");
    $router->get('/{keyword}/keyword', "DynamicFormController@showByKeyword");
    $router->put('/{modelName}/{alternative}', "DynamicFormController@update");
    $router->put('/{modelName}', "DynamicFormController@update");
    $router->delete('/{modelName}', "DynamicFormController@delete");
    $router->put('/update-alternative-layout/{modelName}/{alternative}', "DynamicFormController@updateAlternativeLayout");
});
