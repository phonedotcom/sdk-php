<?php namespace PhoneCom\Sdk\Models\Event;

use PhoneCom\Sdk\Api\Eloquent\Model;
use PhoneCom\Sdk\Api\Ssi\SingleServiceInheritanceTrait;

class Log extends Model
{
    use SingleServiceInheritanceTrait;

    protected static $singleServiceTypeField = 'type';
    protected static $singleServiceSubclasses = [
        'PhoneCom\Sdk\Models\Event\Log\AuthFailureEntry',
        'PhoneCom\Sdk\Models\Event\Log\ListenerFailureEntry',
        'PhoneCom\Sdk\Models\Event\Log\SmsNewInboundEntry',
        'PhoneCom\Sdk\Models\Event\Log\SmsNewOutboundEntry',
    ];

    protected $pathInfo = '/events';

    protected $dates = ['created'];
}
