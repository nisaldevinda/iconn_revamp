<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/dynamic/{modelName}', "DynamicModelDataController@createDynamicModelData");
    $router->get('/dynamic/{modelName}', "DynamicModelDataController@getAllDynamicModelData");
    $router->get('/dynamic/{modelName}/{id}', "DynamicModelDataController@getDynamicModelData");
    $router->get('/dynamic/{modelName}/{keyword}/keyword', "DynamicModelDataController@getDynamicModelDataListByKeyword");
    $router->put('/dynamic/{modelName}/{id}', "DynamicModelDataController@updateDynamicModelData");
    $router->delete('/dynamic/{modelName}/{id}', "DynamicModelDataController@deleteDynamicModelData");
});
