<?php

return [

    /**
     * API usage requires authentication. Fill in your username and password here.
     */
    'username' => env('PHONECOM_USERNAME'),
    'password' => env('PHONECOM_PASSWORD'),

    /**
     * Base URL where the API is located.
     */
    'url' => env('PHONECOM_URL'),

    /**
     * In rare circumstances, you may need to set additional default HTTP headers to be sent with all API requests.
     * This value should be an array. If using an environment variable, the environment variable must be a serialized
     * JSON blob which is decoded to an array at runtime.
     */
    'headers' => @json_decode(env('PHONECOM_HEADERS', '{}'), true),

    /**
     * Whether to operate in debug mode. For performance, set to false.
     */
    'debug' => env('PHONECOM_DEBUG', false)
];
