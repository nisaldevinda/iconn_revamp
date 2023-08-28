<?php

return [

    'expire' => env('COOKIE_EXPIRE', 0),

    'path' => env('COOKIE_PATH', '/'),

    'domain' => env('COOKIE_DOMAIN', null),

    'secure' => env('COOKIE_SECURE', null),

    'same_site' => env('COOKIE_SAME_SITE', 'lax')
];
