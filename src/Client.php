<?php namespace PhoneCom\Sdk;

use Doctrine\Common\Cache\RedisCache;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ClientException;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\PrivateCache;
use PhoneCom\Sdk\Models\QueryBuilder;

class Client
{
    protected $baseUrl = 'https://v2.api.phone.com';
    protected $headers = [];

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    protected function setConfig(array $config)
    {
        return $this
            ->setBaseUrl(@$config['url'])
            ->setHeaders(@$config['headers'])
            ->setDebug(@$config['debug'])
            ->setAuthBasic(@$config['username'], @$config['password']);
    }

    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $this->headers = array_merge($this->headers, $headers);
        }

        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setDebug($debug)
    {
        if ($debug) {
            unset($this->headers['Prefer']);

        } else {
            $this->headers['Prefer'] = 'representation=minimal';
        }

        return $this;
    }

    public function setBaseUrl($baseUrl)
    {
        if ($baseUrl) {
            $this->baseUrl = $baseUrl;
        }

        return $this;
    }

    public function setAuthBasic($username, $password)
    {
        if ($username && $password) {
            $this->headers['Authorization'] = 'Basic ' . base64_encode("$username:$password");

        } else {
            unset($this->headers['Authorization']);
        }

        return $this;
    }

    public function service($pathInfo, array $pathParams = [])
    {
        $query = new QueryBuilder($this);

        return $query
            ->from($pathInfo, $pathParams);
    }

    public function selectOne($url, $options = [])
    {
        $result = $this->select($url, $options);
        if (empty($result->items)) {
            return null;
        }

        return (count($result->items) > 0 ? reset($result->items) : null);
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

    protected function run($verb, $url, $options = [])
    {
        try {
            $stack = HandlerStack::create();
            $stack->push(new CacheMiddleware(new PrivateCache(new RedisCache)), 'cache');

            $client = new Guzzle([
                'handler' => $stack,
                'base_uri' => $this->baseUrl,
                'headers' => $this->headers
            ]);

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
