<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to work calendar controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {

    //pro-rate-formula
    $router->get('/pro-rate-formula-list', "ProRateFormulaController@getProRateFormulaList");
});
