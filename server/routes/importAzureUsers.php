<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */
$router->group(['prefix' => 'api/azure-active-directory', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/import-status', "ImportAzureUsersController@getStatus");
    $router->post('/import-setup', "ImportAzureUsersController@setup");
    $router->get('/field-map', "ImportAzureUsersController@getFieldMap");
    $router->get('/config', "ImportAzureUsersController@getConfig");
    $router->post('/auth-config', "ImportAzureUsersController@storeAuthConfig");
    $router->post('/user-provisioning-config', "ImportAzureUsersController@storeUserProvisioningConfig");
});
