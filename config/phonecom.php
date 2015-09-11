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
    'debug' => env('PHONECOM_DEBUG', false),

    'verify_ssl' => env('PHONECOM_VERIFY_SSL', true),

    'schema' => [
        /**
         * In JSON Schema documents, it is helpful to use the $ref property to point to other snippets of schema
         * in order to avoid repeating the same portions in multiple places. This library provides a way to refer
         * to two sources of these snippets: a shared location for custom definitions, and a separate location for
         * definitions related to the Mason media type. If no URL prefixes are defined below, the default is that
         * $ref will point to the definitions within the current document.
         */
        'ref_url_prefixes' => [
            'shared' => env('PHONECOM_SHARED_REF_PREFIX', ''),
            'mason' => env('PHONECOM_MASON_REF_PREFIX', '')
        ]
    ]
];
