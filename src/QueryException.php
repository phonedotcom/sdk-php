<?php

namespace PhoneCom\Sdk;

use GuzzleHttp\Exception\BadResponseException;
use RuntimeException;

class QueryException extends RuntimeException
{
    /**
     * The SQL for the query.
     *
     * @var string
     */
    protected $sql;

    /**
     * The bindings for the query.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Create a new query exception instance.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception $previous
     * @return void
     */
    public function __construct($verb, $url, $options, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->verb = $verb;
        $this->url = $url;
        $this->options = $options;
        $this->previous = $previous;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($verb, $url, $options, $previous);
    }

    /**
     * Format the SQL error message.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception $previous
     * @return string
     */
    protected function formatMessage($verb, $url, $options, $previous)
    {
        $message = $previous->getMessage();

        if ($previous instanceof BadResponseException) {
            $message .= ' ' . $previous->getResponse()->getBody()->__toString();
        }

        $message .= sprintf(' in response to %s %s %s', strtoupper($verb), $url, json_encode($options));

        return $message;
    }

    /**
     * Get the verb for the query.
     *
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * Get the URL for the query.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the options for the query.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
