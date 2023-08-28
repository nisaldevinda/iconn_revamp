<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    //$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->post('/users', "UserController@store");
    $router->get('/users', "UserController@index");
    $router->get('/users/{id}', "UserController@showById");
    $router->put('/users/{id}/deactivate-user', "UserController@deactivateUser");
    $router->put('/users/{id}', "UserController@update");
    $router->put('/users/{id}/change-active-status', "UserController@changeUserActiveStatus");
    $router->post('/users/{id}/reset-password', "UserController@sendPasswordResetMail");  // route which send the reset email

    $router->post('/users/change-user-password', "UserController@changeUserPassword");

    //get user list for master model
    $router->get('/user-list', "UserController@getUserList");

    //sign in with Microsoft
    $router->get('/get-ms-login-url', "SocialLoginController@generateMicrosoftLoginURL");
    $router->post('/microsoft-login-callback', "SocialLoginController@getMicrosoftEmailFromToken");

    //sign in with Microsoft
    $router->get('/get-ms-login-url', "SocialLoginController@generateMicrosoftLoginURL");
    $router->post('/microsoft-login-callback', "SocialLoginController@getMicrosoftEmailFromToken");

    //sign in with google
    $router->get('/get-google-login-url', "SocialLoginController@generateGoogletLoginURL");
    $router->get('/google-login-callback', "SocialLoginController@getGoogleEmailFromToken");
});


/**
 * All the password manipulation routes doesnt need login.
 */

$router->group(['prefix' => 'api'], function () use ($router) {

    $router->put('/users/{id}/change-password', "UserController@changePassword");
    $router->put('/users/{token}/create-password', "UserController@createPassword");
    $router->get('/users/{token}/is-token-active/{type}', "UserController@isVerificationTokenActive");

    // $router->post('/users/{id}/reset-password', "UserController@sendPasswordResetMail");  // route which send the reset email
    $router->put('/users/{token}/reset-password-by-email', "UserController@resetPasswordByMail"); // route which fetchs the resetted verfication token
    $router->post('/users/forgot-password/', "UserController@forgotPassword");
});
