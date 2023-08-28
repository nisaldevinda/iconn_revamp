<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to leaves.
 */

//manange leave type related routes
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {

  
    $router->get('/leave-types/who-can-apply/{id}',  "LeaveTypeConfigController@getWhoCanApply");
    $router->post('/leave-types/who-can-apply/', "LeaveTypeConfigController@createWhoCanApply"); 
    $router->get('/leave-employee-groups',  "LeaveTypeConfigController@getAllEmployeeGroups");
    $router->get('/leave-type-wise-accruals',  "LeaveTypeConfigController@getAllLeaveTypeWiseAccruals");
    $router->put('/leave-employee-groups/{id}',  "LeaveTypeConfigController@updateEmployeeGroup");
    $router->delete('/leave-employee-groups/{id}',  "LeaveTypeConfigController@deleteLeaveEmployeeGroup");
    $router->get('/leave-employee-groups-by-leave-type',  "LeaveTypeConfigController@getEmployeeGroupsByLeaveTypeId");
    $router->put('/leave-type-accrual-configs/{id}',  "LeaveTypeConfigController@setLeaveTypeAccrualConfigs");
    $router->post('/leave-accrual-config',  "LeaveTypeConfigController@createLeaveAccrualConfig");
    $router->put('/leave-accrual-config/{id}',  "LeaveTypeConfigController@updateLeaveAccrualConfig");
    $router->get('/leave-type-accrual-configs',  "LeaveTypeConfigController@getLeaveTypeAccrualConfigsByLeaveTypeId");
  
});