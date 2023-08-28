<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to attendance controller.
 */

$router->group(['prefix' => 'api'], function () use ($router) {
        $router->group(['middleware' => ['jwt']], function () use ($router) {
                $router->get('/attendance', "AttendanceController@getAttendance");
                $router->post('/attendance', "AttendanceController@manageAttendance");
                $router->post('/attendance/break', "AttendanceController@manageBreak");
                $router->get('/attendance/getLastLogged', "AttendanceController@getLastLoggedTime");

        });

        $router->post('/attendance/sync', "AttendanceController@createAttendanceLog");
});
