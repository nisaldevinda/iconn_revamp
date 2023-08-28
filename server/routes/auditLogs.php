<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/audit-logs', "AuditLogController@index");
    $router->get('/audit-logs/{id}/employeeId', "AuditLogController@showByEmployeeId");
});
