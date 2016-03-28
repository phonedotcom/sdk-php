<?php namespace Phonedotcom\Sdk\Models\Event\Log;

use Phonedotcom\Sdk\Models\Event\Log;

class SmsNewOutboundEntry extends Log
{
    protected static $singleServiceType = 'sms.new.outbound';

    protected $staticRelationMap = [
        'sms' => 'Phonedotcom\Sdk\Models\Sms'
    ];
}
