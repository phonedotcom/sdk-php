<?php namespace PhoneCom\Sdk\Models\Event;

use PhoneCom\Sdk\Api\Eloquent\Model;
use PhoneCom\Sdk\Api\Ssi\SingleServiceInheritanceTrait;

class Listener extends Model
{
    use SingleServiceInheritanceTrait;

    protected static $singleServiceTypeField = 'type';
    protected static $singleServiceSubclasses = [
        'PhoneCom\Sdk\Models\Event\Listener\HttpPostJsonListener'
    ];

    protected $pathInfo = '/listeners';
}
