<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to notice controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/notices/{id}', "NoticeController@getNotice");
    $router->get('/notices', "NoticeController@getAllNotices");
    $router->post('/notices', "NoticeController@createNotice");
    $router->put('/notices/{id}', "NoticeController@updateNotice");
    $router->delete('/notices/{id}', "NoticeController@deleteNotice");
    $router->get('/admin/dashboard-notices', "NoticeController@getAdminRecentlyPublishedNotices");
    $router->get('/manager/dashboard-notices', "NoticeController@getManagerRecentlyPublishedNotices");
    $router->get('/employee/dashboard-notices', "NoticeController@getEmployeeRecentlyPublishedNotices");

});
