<?php

return [

     /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'app'),
    'portal' => env('PORTAL_DB_CONNECTION', 'portal'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        /*'testing' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ],*/

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', base_path('database/database.sqlite')),
            'prefix'   => env('DB_PREFIX', ''),
        ],

        'app' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', 3306),
            'database'  => env('DB_DATABASE', 'iconnhrm_2'), //getenv('TENANTDB')
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', 'password'),
            'charset'   => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('DB_PREFIX', ''),
            'timezone'  => env('DB_TIMEZONE', '+00:00'),
            'strict'    => env('DB_STRICT_MODE', false),
        ],

        'portal' => [
            'driver'    => 'mysql',
            'host'      => env('PORTAL_DB_HOST', 'localhost'),
            'port'      => env('PORTAL_DB_PORT', 3306),
            'database'  => env('PORTAL_DB_DATABASE', 'portal'),
            'username'  => env('PORTAL_DB_USERNAME', 'root'),
            'password'  => env('PORTAL_DB_PASSWORD', 'password'),
            'charset'   => env('PORTAL_DB_CHARSET', 'utf8'),
            'collation' => env('PORTAL_DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => env('PORTAL_DB_PREFIX', ''),
            'timezone'  => env('PORTAL_DB_TIMEZONE', '+00:00'),
            'strict'    => env('PORTAL_DB_STRICT_MODE', false),
        ],
    ],

      /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],
];
