<?php

return [
    'version' => env('FILE_STORAGE_VERSION', 'latest'),
    'region' => env('FILE_STORAGE_REGION', 'us-east-1'),
    'endpoint' => env('FILE_STORAGE_ENDPOINT', 'http://localhost:9000'),
    'key' => env('FILE_STORAGE_KEY', 'AKIAIOSFODNN7EXAMPLE'),
    'secret' => env('FILE_STORAGE_SECRET', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
    'urlExpireTime' => env('FILE_STORAGE_FILE_URL_EXPIRE_TIME', 30),
    'defaultBucket' => env('FILE_STORAGE_DEFAULT_BUCKET', 'root'),
    'bucketPrefix' => env('FILE_STORAGE_BUCKET_PREFIX', 'iconnhrm')
];
