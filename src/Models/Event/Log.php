<?php namespace Phonedotcom\Sdk\Models\Event;

use Phonedotcom\Sdk\Api\Eloquent\Model;
use Phonedotcom\Sdk\Api\Ssi\SingleServiceInheritanceTrait;

class Log extends Model
{
    use SingleServiceInheritanceTrait;

    protected static $singleServiceTypeField = 'type';
    protected static $singleServiceSubclasses = [
        'Phonedotcom\Sdk\Models\Event\Log\AuthFailureEntry',
        'Phonedotcom\Sdk\Models\Event\Log\ListenerFailureEntry',
        'Phonedotcom\Sdk\Models\Event\Log\SmsNewInboundEntry',
        'Phonedotcom\Sdk\Models\Event\Log\SmsNewOutboundEntry',
    ];

    protected $pathInfo = '/events';

    protected $dates = ['created'];
}
