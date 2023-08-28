<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to audit trail controller.
 */
$router->group(['prefix' => 'api/audit-trail', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/', "AuditTrailController@getAll");
});
