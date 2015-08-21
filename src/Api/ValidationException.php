<?php
namespace PhoneCom\Sdk\Api;

class ValidationException extends \Exception
{
    protected $errors = [];

    public function __construct($message, array $errors)
    {
        $this->errors = $errors;
        $this->message = $this->formatMessage($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function formatMessage($message = '')
    {
        return ($message ?: 'Validation failed:') . ' ' . json_encode($this->errors, JSON_UNESCAPED_SLASHES);
    }
}
