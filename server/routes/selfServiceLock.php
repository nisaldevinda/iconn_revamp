<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to scheme controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/self-service-lock-date-periods', "SelfServiceLockController@createDatePeriods");
    $router->get('/self-service-lock-date-periods', "SelfServiceLockController@getDatePeriods");
    $router->get('/get-all-self-service-lock-date-periods', "SelfServiceLockController@getAllDatePeriods");
    $router->get('/self-service-lock-date-periods/{id}', "SelfServiceLockController@getDatePeriod");
    $router->put('/self-service-lock-date-periods/{id}', "SelfServiceLockController@updateDatePeriods");
    $router->delete('/self-service-lock-date-periods/{id}', "SelfServiceLockController@deleteDatePeriods");
});


$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/self-service-lock-configs', "SelfServiceLockController@createSelfServiceLockConfig");
    $router->get('/self-service-lock-configs', "SelfServiceLockController@getAllSelfServiceLockConfigs");
    $router->get('/self-service-lock-configs/{id}', "SelfServiceLockController@getSelfServiceLockConfig");
    $router->put('/self-service-lock-configs/{id}', "SelfServiceLockController@updateSelfServiceLockConfig");
    $router->delete('/self-service-lock-configs/{id}', "SelfServiceLockController@deleteSelfServiceLockConfig");
});
