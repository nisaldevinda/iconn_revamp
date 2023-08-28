<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/assign-shifts', "ShiftAssignController@store");
    $router->get('/assign-shifts/{id}', "ShiftAssignController@getById");
    $router->put('/assign-shift/{id}', "ShiftAssignController@update");
    $router->get('/unassigned-employees-list', "ShiftAssignController@getShiftUnassignedEmployees");
});
