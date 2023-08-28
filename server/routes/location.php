<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to location controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/locations', "LocationController@getAllLocations");
    $router->get('/locations/{id}', "LocationController@getLocation");
    $router->post('/locations', "LocationController@createLocation");
    $router->put('/locations/{id}', "LocationController@updateLocation");
    $router->delete('/locations/{id}', "LocationController@deleteLocation");

    $router->get('/locationByCountryId' ,"LocationController@getLocationByCountryId");
    $router->get('/adminAccessLocations' ,"LocationController@getAdminUserAccessLocations");
    
});
