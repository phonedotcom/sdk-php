<?php namespace PhoneCom\Sdk;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use PhoneCom\Sdk\Models\QueryBuilder;
use PhoneCom\Sdk\Models\QueryException;
use PhoneCom\Sdk\Exceptions\BadConfigurationException;

class Client
{
    private $guzzleClient;

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
            $response = $this->getGuzzleClient()->request($verb, $url, $options);

            if (/*$response->getHeaderLine('X-KevinRob-Cache') != 'HIT'
                &&*/ $response->getHeaderLine('Content-Type') != 'application/vnd.mason+json'
            ) {
                throw new \Exception(
                    'API response is not a Mason document: ' . $this->truncate($response->getBody()->__toString(), 200)
                );
            }

        } catch (\Exception $e) {
            if ($e instanceof ClientException && $e->getCode() == 401) {
                throw new BadConfigurationException('Missing or invalid API login credentials');
            }

            throw new QueryException($verb, $url, $options, $e);
        }

        return @json_decode($response->getBody()->__toString());
    }

    private function truncate($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    private function getGuzzleClient()
    {
        if (!$this->guzzleClient) {
            $stack = HandlerStack::create();
            //$this->addHttpCaching($stack);

            $this->guzzleClient = new Guzzle([
                'handler' => $stack,
                'base_uri' => $this->baseUrl,
                'headers' => $this->headers
            ]);
        }

        return $this->guzzleClient;
    }

    /* Commented out because we need a better way to let non-Laravel deployments use caching. If/When we
     * get back to this, using Redis and tying into Laravel's Redis tools will require the following Composer
     * dependencies:
     *     "kevinrob/guzzle-cache-middleware": "^0.5",
     *     "snc/redis-bundle": "^1.1"
     * And we will need the following USE statements above:
     *     use Snc\RedisBundle\Doctrine\Cache\RedisCache;
     *     use Kevinrob\GuzzleCache\CacheMiddleware;
     *     use Kevinrob\GuzzleCache\PrivateCache;
     * /
    private function addHttpCaching(HandlerStack $stack)
    {
        $redisCache = new RedisCache;
        $redisCache->setRedis(app('redis')->connection());
        $stack->push(new CacheMiddleware(new PrivateCache($redisCache)), 'cache');
    }
    */
}
