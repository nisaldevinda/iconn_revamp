<?php

$router->group(['prefix' => 'rest-api', 'middleware' => ['client', 'scope:payroll']], function () use ($router) {
    $router->get('/route-a', function () use ($router) {
        return response()->json(['message' => 'Sucsses !', 'data' => 'A'], 200);
    });

    $router->get('/route-b', function () use ($router) {
        return response()->json(['message' => 'Sucsses !', 'data' => 'B'], 200);
    });

    $router->get('/route-c', function () use ($router) {
        return response()->json(['message' => 'Sucsses !', 'data' => 'C'], 200);
    });
});

$router->group(['prefix' => 'rest-api', 'middleware' => []], function () use ($router) {
    $router->get('/route-x', function () use ($router) {
        return response()->json(['message' => 'Sucsses !', 'data' => 'X'], 200);
    });
});