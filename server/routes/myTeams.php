<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to division controller.
 */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->group(['middleware' => ['jwt']], function () use ($router) {

        // route to get employee team data
        $router->get('/my-teams', "MyTeamsController@getMyTeams");

        // routes to manipulate team employee data
        $router->get('/my-teams-employees', "MyTeamsController@getAllEmployees");
        $router->get('/my-teams-employees/{id}', "MyTeamsController@getEmployee");
        $router->post('/my-teams-employees', "MyTeamsController@createEmployee");
        $router->put('/my-teams-employees/{id}', "MyTeamsController@updateEmployee");
        $router->delete('/my-teams-employees/{id}', "MyTeamsController@deleteEmployee");

        // routes to manipulate team employee multi record feilds
        $router->post('/my-teams-employees/{id}/{multirecordAttribute}', "MyTeamsController@createEmployeeMultiRecord");
        $router->put('/my-teams-employees/{id}/{multirecordAttribute}/{multirecordId}', "MyTeamsController@updateEmployeeMultiRecord");
        $router->delete('/my-teams-employees/{id}/{multirecordAttribute}/{multirecordId}', "MyTeamsController@deleteEmployeeMultiRecord");

    });
});
