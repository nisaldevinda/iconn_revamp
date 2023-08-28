<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/leave-entitlement', "LeaveEntitlementController@store");
    $router->post('/leave-entitlement-multiple', "LeaveEntitlementController@storeMultiple");

    $router->get('/leave-entitlement', "LeaveEntitlementController@index");
    $router->get('/my-leave-entitlement', "LeaveEntitlementController@myEntitlements");

    //$router->get('/leave-entitlement/{id}', "LeaveEntitlementController@showById");
   // $router->get('/leave-entitlement/filteredEmployees', "LeaveEntitlementController@getEmployeesByKeyword");
    $router->put('/leave-entitlement/{id}', "LeaveEntitlementController@update");
    $router->delete('/leave-entitlement/{id}', "LeaveEntitlementController@delete");
    $router->get('/leave-entitlement/employees', "LeaveEntitlementController@getExistingEmployees");
    $router->get('/leave-entitlement/leave-types', "LeaveEntitlementController@getExistingLeaveTypes");
    $router->get('/leave-entitlement/leave-periods', "LeaveEntitlementController@getExistingLeavePeriods");
    $router->get('/check-entitlement-availability', "LeaveEntitlementController@checkEntitlementAvailability");



});
