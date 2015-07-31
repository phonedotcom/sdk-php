<?php

namespace PhoneCom\Sdk;

use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Throwable;
use PhoneCom\Sdk\Query\Processors\Processor;
use PhoneCom\Sdk\Query\Builder as QueryBuilder;
use PhoneCom\Sdk\Query\Grammars\Grammar as QueryGrammar;

class Connection implements ConnectionInterface
{
    /**
     * The query grammar implementation.
     *
     * @var \PhoneCom\Sdk\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The query post processor implementation.
     *
     * @var \PhoneCom\Sdk\Query\Processors\Processor
     */
    protected $postProcessor;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;

    protected $baseUri = [];
    protected $headers = [];

    /**
     * Create a new database connection instance.
     *
     * @param  \PDO     $pdo
     * @param  string   $database
     * @param  string   $tablePrefix
     * @param  array    $config
     * @return void
     */
    public function __construct($baseUri, array $headers = [])
    {
        $this->baseUri = $baseUri;
        $this->headers = $headers;
        $this->useDefaultQueryGrammar();
        $this->useDefaultPostProcessor();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \PhoneCom\Sdk\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \PhoneCom\Sdk\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string  $table
     * @return \PhoneCom\Sdk\Query\Builder
     */
    public function service($pathInfo, array $params = [])
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $this->getQueryGrammar(), $processor);

        return $query->from($pathInfo, $params);
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($url, $options = [])
    {
        $records = $this->select($url, $options);

        return count($records) > 0 ? reset($records) : null;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($url, $options = [])
    {
        return $this->run('GET', $url, $options, function ($me, $verb, $url, $options) {

            $client = new Client([
                'base_uri' => $me->baseUri,
                'headers' => $me->headers
            ]);

            $response = $client->request($verb, $url, $options);

            if ($response->getHeaderLine('Content-Type') != 'application/vnd.mason+json') {
                throw new \Exception('API response is not a Mason document');
            }

            return @json_decode($response->getBody()->__toString());
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($url, $options = [])
    {
        return $this->statement('POST', $url, $options);
    }

    /**
     * Run an update statement against the database.
     *
     * @return int
     */
    public function update($url, $options = [])
    {
        return $this->statement('PUT', $url, $options);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($url, $options = [])
    {
        return $this->statement('DELETE', $url, $options);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($verb, $url, $options = [])
    {
        return $this->run($verb, $url, $options, function ($me, $verb, $url, $options) {

            $client = new Client([
                'base_uri' => $me->baseUri,
                'headers' => $me->headers
            ]);

            $response = $client->request($verb, $url, $options);

            if ($response->getHeaderLine('Content-Type') != 'application/vnd.mason+json') {
                throw new \Exception('API response is not a Mason document');
            }

            return @json_decode($response->getBody()->__toString());
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($verb, $url, $options = [])
    {
        return $this->statement($verb, $url, $options);
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \PhoneCom\Sdk\QueryException
     */
    protected function run($verb, $url, $options, Closure $callback)
    {
        $start = microtime(true);

        try {
            $result = $this->runQueryCallback($verb, $url, $options, $callback);

        } catch (QueryException $e) {
            $result = $this->tryAgainIfCausedByLostConnection(
                $e, $verb, $url, $options, $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->getElapsedTime($start);

        $this->logQuery($verb, $url, $options, $time);

        return $result;
    }

    /**
     * Run a SQL statement.
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \PhoneCom\Sdk\QueryException
     */
    protected function runQueryCallback($verb, $url, $options, Closure $callback)
    {
        try {
            $result = $callback($this, $verb, $url, $options);

        } catch (Exception $e) {
            throw new QueryException($verb, $url, $options, $e);
        }

        return $result;
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  \PhoneCom\Sdk\QueryException  $e
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \PhoneCom\Sdk\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $verb, $url, $options, Closure $callback)
    {
        if ($this->causedByLostConnection($e)) {

            return $this->runQueryCallback($verb, $url, $options, $callback);
        }

        throw $e;
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \PhoneCom\Sdk\QueryException  $e
     * @return bool
     */
    protected function causedByLostConnection(QueryException $e)
    {
        // TODO: What does "lost connection" mean in an API context?  Connection Timeout? How to detect this?

        $message = $e->getPrevious()->getMessage();

        return false;
        /*
        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
        ]);
        */
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  float|null  $time
     * @return void
     */
    public function logQuery($verb, $url, $options, $time = null)
    {
        if (!$this->loggingQueries) {
            return;
        }

        $this->queryLog[] = compact('verb', 'url', 'options', 'time');
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \PhoneCom\Sdk\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  \PhoneCom\Sdk\Query\Grammars\Grammar  $grammar
     * @return void
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \PhoneCom\Sdk\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param  \PhoneCom\Sdk\Query\Processors\Processor  $processor
     * @return void
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

}
