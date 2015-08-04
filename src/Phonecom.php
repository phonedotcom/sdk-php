<?php namespace PhoneCom\Sdk;

use PhoneCom\Sdk\Models\Client;
use PhoneCom\Sdk\Models\Model;

class Phonecom
{
    private static $baseUrl = 'https://v2.api.phone.com';
    private static $defaultHeaders = [];

    protected function __construct()
    {
    }

    /**
     * @var Client
     */
    private static $client;

    public static function setCredentials($authUser, $authPassword)
    {
        self::getClient()->setAuthBasic($authUser, $authPassword);
    }

    public static function setBaseUrl($baseUrl)
    {
        self::$baseUrl = $baseUrl;
        self::getClient()->setBaseUrl($baseUrl);
    }

    public static function setDefaultHeaders(array $headers)
    {
        self::$defaultHeaders = $headers;
        self::getClient()->setDefaultHeaders($headers);
    }

    private static function getClient()
    {
        if (!self::$client) {
            self::$client = new Client(self::$baseUrl, self::$defaultHeaders);
            Model::setClient(self::$client);
        }

        return self::$client;
    }
}
