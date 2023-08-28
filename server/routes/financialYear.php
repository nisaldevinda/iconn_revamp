<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api','middleware' => ['jwt']], function () use ($router) {
    $router->get('/financialYears', "FinancialYearController@getAllFinancialYears");
    $router->get('/financialYears/{id}', "FinancialYearController@getFinancialYear");
    $router->post('/financialYears', "FinancialYearController@createFinancialYear");
    $router->put('/financialYears/{id}', "FinancialYearController@updateFinancialYear");
    $router->delete('/financialYears/{id}', "FinancialYearController@deleteFinancialYear");
    
});