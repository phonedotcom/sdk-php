<?php namespace PhoneCom\Sdk;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PhoneCom\Sdk\Models\QueryBuilder;
use PhoneCom\Sdk\Exceptions\BadConfigurationException;
use PhoneCom\Sdk\Exceptions\QueryException;
use PhoneCom\Sdk\Exceptions\ValidationException;

class Client
{
    protected $baseUrl = 'https://v2.api.phone.com';
    protected $headers = [];

    /**
     * List of responses to dole out each time Guzzle is asked to get something. Used only for testing.
     * @var array
     */
    private static $mockResponses;

    public static function setMockResponses(array $responses = [])
    {
        foreach ($responses as $response) {
            if (!$response instanceof Response) {
                throw new \InvalidArgumentException(sprintf(
                    'Response must be an instance of %s, but %s was given',
                    Response::class,
                    (is_object($response) ? get_class($response) : gettype($response))
                ));
            }
        }

        self::$mockResponses = $responses;
    }

    public static function flushMockResponses()
    {
        self::$mockResponses = null;
    }

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
        try {
            return $this->run('GET', $url, $options);

        } catch (QueryException $e) {
            if ($e->getPrevious()->getCode() == 404) {
                return null;
            }

            throw $e;
        }
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
            if ($e instanceof ClientException) {
                if ($e->getCode() == 401) {
                    throw new BadConfigurationException('Missing or invalid API login credentials');

                } elseif ($e->getCode() == 422) {
                    $data = @json_decode($e->getResponse()->getBody()->__toString(), true);

                    throw new ValidationException('', $data['@error']['fields']);
                }
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
        $stack = HandlerStack::create();

        if (self::$mockResponses) {
            $handler = new MockHandler(self::$mockResponses);
            $stack->setHandler($handler);
        }

        //$this->addHttpCaching($stack);

        return new HttpClient([
            'handler' => $stack,
            'base_uri' => $this->baseUrl,
            'headers' => $this->headers
        ]);
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
