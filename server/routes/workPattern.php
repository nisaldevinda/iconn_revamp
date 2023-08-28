<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
  
    $router->get('/work-patterns', "WorkPatternController@list");
    $router->get('/work-patterns/{id}', "WorkPatternController@getById");
    $router->get('/get-work-pattern-employees/{id}', "WorkPatternController@getWorkPaternRelatedEmployees");
    $router->get('/list-work-patterns', "WorkPatternController@listAllWorkPatterns");
    $router->post('/work-patterns', "WorkPatternController@store");
    $router->put('/work-patterns/{id}', "WorkPatternController@update");
    $router->delete('/work-patterns/{id}', "WorkPatternController@delete");
    $router->post('/duplicate-work-patterns', "WorkPatternController@createDuplicatePattern");
    $router->post('/assign-work-patterns', "WorkPatternController@assignWorkPatterns");
    $router->put('/delete-week/{id}', "WorkPatternController@deleteWeek");
});
