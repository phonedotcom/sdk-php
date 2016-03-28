<?php namespace Phonedotcom\Sdk\Api;

use GuzzleHttp\Exception\BadResponseException;
use RuntimeException;

class QueryException extends RuntimeException
{
    protected $verb;
    protected $url;
    protected $options;

    public function __construct($verb, $url, $options, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->verb = $verb;
        $this->url = $url;
        $this->options = $options;
        $this->previous = $previous;
        $this->message = $this->formatMessage($verb, $url, $options, $previous);
    }

    protected function formatMessage($verb, $url, $options, $previous)
    {
        $message = $previous->getMessage();

        if ($previous instanceof BadResponseException) {
            $message .= ' ' . $previous->getResponse()->getBody()->__toString();
        }

        $message .= sprintf(' in response to %s %s %s', strtoupper($verb), $url, json_encode($options));

        return $message;
    }

    public function getVerb()
    {
        return $this->verb;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
