<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to country and state controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/countries', "CountryAndStateController@getAllcountries");
    $router->get('/countries/{countryId}', "CountryAndStateController@getcountryBycountryId");
    $router->get('/countries/{countryId}/states', "CountryAndStateController@getAllStatesBycountryId");
    $router->get('/countries/states/{stateId}', "CountryAndStateController@getStateByStateId");
    $router->get('/countries-list-for-work-patterns', "CountryAndStateController@getCountriesListForWorkPatterns");
});
