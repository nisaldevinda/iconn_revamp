<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to promotion types controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/promotion-types', "PromotionTypeController@store");
    $router->get('/promotion-types', "PromotionTypeController@index");
    $router->get('/promotion-types/{id}', "PromotionTypeController@showById");
    $router->get('/promotion-types/{keyword}/keyword', "PromotionTypeController@showByKeyword");
    $router->put('/promotion-types/{id}', "PromotionTypeController@update");
    $router->delete('/promotion-types/{id}', "PromotionTypeController@delete");
});
