<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to department controller.
 */

$router->group(['prefix' => 'api', 'middleware' => 'jwt'],function () use ($router) {
    $router->get('/departments', "DepartmentController@getAllDepartments");
    $router->get('/departments-raw', "DepartmentController@getAllRawDepartments");
    $router->get('/departments-employee/{departmentID}/employees', "DepartmentController@getEmployeesByDepartmentID");
    $router->get('/departments-tree-generator', "DepartmentController@generateDepartmentTree");
    $router->get('/manager-org-chart-data', "DepartmentController@getManagerOrgChartData");
    $router->get('/manager-isolated-org-chart-data/{id}', "DepartmentController@getManagerIsolatedOrgChartData");
    $router->get('/departments/{id}', "DepartmentController@getDepartment");
    $router->post('/departments', "DepartmentController@createDepartment");
    $router->put('/departments/{id}', "DepartmentController@updateDepartment");
    $router->delete('/departments/{id}', "DepartmentController@deleteDepartment");
    $router->post('/org-entities', "DepartmentController@addEntity");
    $router->put('/org-entities/{id}', "DepartmentController@editEntity");
    $router->delete('/org-entities/{id}', "DepartmentController@deleteEntity");
    $router->get('/org-entities', "DepartmentController@getAllEntities");
    $router->get('/org-entities/{id}', "DepartmentController@getEntity");
    $router->post('/org-entities/{entityLevel}/can-delete', "DepartmentController@canDelete");
});
