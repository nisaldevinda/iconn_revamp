<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/workSchedules', "WorkScheduleController@store");
    $router->get('/workSchedules', "WorkScheduleController@index");
    $router->get('/employee-work-pattern', "WorkScheduleController@getEmployeeWorkPattern");
    $router->post('/employee-work-pattern',"WorkScheduleController@createEmployeeWorkPattern");
    $router->get('/workSchedules/workShifts', "WorkScheduleController@getWorkShifts");
    $router->get('/workSchedules/workShifts/{id}', "WorkScheduleController@getWorkShiftById");
    $router->get('/work-schedules-manager-view', "WorkScheduleController@getWorkScheduleManagerView");
    $router->get('/my-work-schedule', "WorkScheduleController@getMyWorkSchedule");
    $router->get('/employee-work-schedule', "WorkScheduleController@getEmployeeWorkSchedule");
    $router->get('/get-date-wise-employee-work-shift', "WorkScheduleController@getEmployeeWorkShiftByDate");
    $router->get('/workSchedules/get-work-shifts-for-shift-change', "WorkScheduleController@getWorkShiftsForShiftChange");
});