<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/payments/create-checkout-session', "PaymentController@createCheckoutSession");
    $router->get('/payments/info', "PaymentController@info");
    $router->get('/payments/payment-methods', "PaymentController@getPaymentMethods");
    $router->put('/payments/update-subscription', "PaymentController@updateSubscription");
    $router->post('/payments/cancel-subscription', "PaymentController@cancelSubscription");
    $router->post('/payments/reactivate-subscription', "PaymentController@reactivateSubscription");
    $router->get('/payments/list', "PaymentController@getPayments");
});