<?php

namespace PhoneCom\Sdk\Api\Ssi\Exceptions;

class SingleServiceInheritanceInvalidAttributesException extends SingleServiceInheritanceException
{
    protected $invalidAttributes;

    public function __construct($message, array $invalidAttributes)
    {
        parent::__construct($message . "The attributes: " . implode(',', $invalidAttributes) . " are invalid.");
        $this->invalidAttributes = $invalidAttributes;
    }

    public function getInvalidAttributes()
    {
        return $this->invalidAttributes;
    }
}
