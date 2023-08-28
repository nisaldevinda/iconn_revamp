<?php

return [
    'scheme' => env('REDIS_SCHEME', 'tcp'),
    'host' => env('REDIS_HOST', 'localhost'),
    'password' => env('REDIS_PASSWORD', null),
    'port' => env('REDIS_PORT', 6379),
];
