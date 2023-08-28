<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/employee-number-configurations', "EmployeeNumberConfigController@getAllEmployeeNumberConfigs");
    $router->get('/employee-number-configurations/{id}', "EmployeeNumberConfigController@getEmployeeNumberConfigs");
    $router->post('/employee-number-configurations', "EmployeeNumberConfigController@addEmployeeNumberConfigs");
    $router->put('/employee-number-configurations/{id}', "EmployeeNumberConfigController@updateEmployeeNumberConfigs");
    $router->delete('/employee-number-configurations/{id}', "EmployeeNumberConfigController@removeEmployeeNumberConfigs");
});
