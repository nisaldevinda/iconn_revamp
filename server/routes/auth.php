<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to auth controller.
 */

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/authentication', 'AuthController@authentication');
    $router->post('/sso-login', 'AuthController@ssoLogin');
    $router->post('/logout', 'AuthController@logout');
    $router->post('/v1/oauth/token', "AccessTokenController@issueToken");
    $router->post('/mobile-authentication', 'AuthController@mobileAuthentication');
    $router->get('/finalizing-setup/{verificationToken}', "AuthController@finalizingSetup");
    $router->get('/companies/images', "CompanyController@getImages");

    $router->group(['middleware' => 'jwt'], function () use ($router) {
        $router->get('/authenticated-user', 'AuthController@getAuthenticatedUser');
    });
});
