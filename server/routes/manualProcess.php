<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/manual-processes', "ManualProcessController@run");
    $router->get('/manual-process-history', "ManualProcessController@history");
    $router->get('/leave-accrual-employee-list', "ManualProcessController@getLeaveAccrualProcessEmployeeList");
});
