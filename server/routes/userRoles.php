<?php


$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/userRoles', "UserRoleController@getAllUserRoles");
    // $router->get('/userRoles', "UserRoleController@index");
    $router->post('/userRoles', "UserRoleController@createUserRole");
    $router->get('/userRoles/access-management-fields', "UserRoleController@getAccessManagementFields");
    $router->get('/userRoles/access-management-mandotary-fields', "UserRoleController@getAccessManagementMandatoryFields");
    $router->get('/userRoles/{id}', "UserRoleController@getUserRole");
    $router->put('/userRoles/{id}', "UserRoleController@updateUserRole");
    $router->delete('/userRoles/{id}', "UserRoleController@deleteUserRole");

    $router->get('/userRolesMeta', "UserRoleController@getAllUserRoleMeta");
    $router->get('/get-admin-roles', "UserRoleController@getAllAdminUserRoles");
});
