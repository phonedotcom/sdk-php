<?php namespace Phonedotcom\Sdk\Models\Event;

use Phonedotcom\Sdk\Api\Eloquent\Model;
use Phonedotcom\Sdk\Api\Ssi\SingleServiceInheritanceTrait;

class Listener extends Model
{
    use SingleServiceInheritanceTrait;

    protected static $singleServiceTypeField = 'type';
    protected static $singleServiceSubclasses = [
        'Phonedotcom\Sdk\Models\Event\Listener\HttpPostJsonListener'
    ];

    protected $pathInfo = '/listeners';
}
