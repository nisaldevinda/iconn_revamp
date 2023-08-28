<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to payRoll.
 */

//manange payRoll related routes
$router->group(['prefix' => 'api', 'middleware' => ['client', 'scope:payroll']], function () use ($router) {
    $router->get('/get-employee-profiles',  "PayRollController@getEmployeeProfilesForPayRoll");
    $router->get('/get-employee-attendance-summery',  "PayRollController@getEmployeeAttendanceSummeryForPayRoll");
    $router->put('/change-attedance-record-state',  "PayRollController@changeAttendanceRecordsStateForPayRoll");
    $router->post('/payroll/upload-payslips',  "PayRollController@uploadPayslips");
});

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/get-recent-payslips', "PayRollController@getRecentPayslips");
    $router->get('/get-payslip/{id}', "PayRollController@getPayslip");
});

