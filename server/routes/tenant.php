<?php

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/tenant-verfication', 'TenantController@verification');
});
