<?php namespace PhoneCom\Sdk\Models;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;

class Client
{
    protected $baseUrl = [];
    protected $headers = [];

    public function __construct($baseUrl, array $headers = [])
    {
        $this->baseUrl = $baseUrl;
        $this->headers = $headers;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function setDefaultHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function setAuthBasic($username, $password)
    {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("$username:$password");
    }

    public function service($pathInfo, array $pathParams = [])
    {
        $query = new QueryBuilder($this);

        return $query
            ->from($pathInfo, $pathParams);
    }

    public function selectOne($url, $options = [])
    {
        $records = $this->select($url, $options)->items;

        return (count($records) > 0 ? reset($records) : null);
    }

    public function select($url, $options = [])
    {
        return $this->run('GET', $url, $options);
    }

    public function insert($url, $options = [])
    {
        return $this->run('POST', $url, $options);
    }

    public function update($url, $options = [])
    {
        return $this->run('PUT', $url, $options);
    }

    public function delete($url, $options = [])
    {
        return $this->run('DELETE', $url, $options);
    }

    public function run($verb, $url, $options = [])
    {
        try {
            // TODO: Guzzle HTTP caching!

            $client = new Guzzle(['base_uri' => $this->baseUrl, 'headers' => $this->headers]);

            $response = $client->request($verb, $url, $options);

            if ($response->getHeaderLine('Content-Type') != 'application/vnd.mason+json') {
                throw new \Exception('API response is not a Mason document');
            }

        } catch (\Exception $e) {
            throw new QueryException($verb, $url, $options, $e);
        }

        return @json_decode($response->getBody()->__toString());
    }
}
