<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to company controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
        $router->get('/companies', "CompanyController@getCompany");
        $router->put('/companies/{id}', "CompanyController@updateCompany");
        $router->post('/companies/images', "CompanyController@storeImages");
    // });
});
