<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to work calendar controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {

    //work-calendar-day-type
    $router->post('/pay-types', "PayTypeController@createPayType");
    $router->get('/pay-types', "PayTypeController@getPayTypeList");
    $router->get('/get-ot-pay-type-list', "PayTypeController@getOTPayTypeList");
    $router->put('/pay-types/{id}', "PayTypeController@updatePayType");
    $router->delete('/pay-types/{id}', "PayTypeController@deletePayType");
});
