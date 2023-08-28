<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to notice category controller.
 */
$router->group(['prefix' => 'api/notice-categories', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/', "NoticeCategoryController@store");
    $router->get('/', "NoticeCategoryController@index");
    $router->get('/{id}', "NoticeCategoryController@showById");
    $router->get('/{keyword}/keyword', "NoticeCategoryController@showByKeyword");
    $router->put('/{id}', "NoticeCategoryController@update");
    $router->delete('/{id}', "NoticeCategoryController@delete");
});
