<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
  
    $router->get('/work-shifts', "WorkShiftController@list");
    $router->get('/work-shifts/{id}', "WorkShiftController@getById");
    $router->get('/list-work-shifts', "WorkShiftController@listAllWorkShifts");
    $router->post('/work-shifts', "WorkShiftController@store");
    $router->put('/work-shifts/{id}', "WorkShiftController@update");
    $router->delete('/work-shifts/{id}', "WorkShiftController@delete");
    $router->get('/work-shift-day-type', "WorkShiftController@getWorkShiftDayType");
    $router->get('/work-shifts-list', "WorkShiftController@getWorkShiftsList");
    $router->post('/save-shift-change-request', "ShiftChangeRequestController@createShiftChangeRequest");

    
    $router->post('/adhoc-work-shifts', "WorkShiftController@createAdhocWorkShift");
    //work shift pay configurations
    $router->put('/work-shift-pay-configuration/{id}', "WorkShiftPayCofigurationController@setPayConfigurations");
    $router->get('/work-shift-pay-configuration/{id}', "WorkShiftPayCofigurationController@getPayConfiguration");
    $router->get('/get-time-base-pay-config-state', "WorkShiftPayCofigurationController@getTimeBasePayConfigState");
  
});
