<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/notice-period-configs', "NoticePeriodConfigController@store");
    $router->get('/notice-period-configs', "NoticePeriodConfigController@index");
    $router->get('/notice-period-configs/{id}', "NoticePeriodConfigController@showById");
    $router->get('/notice-period-configs/{keyword}/keyword', "NoticePeriodConfigController@showByKeyword");
    $router->put('/notice-period-configs/{id}', "NoticePeriodConfigController@update");
    $router->delete('/notice-period-configs/{id}', "NoticePeriodConfigController@delete");
});
