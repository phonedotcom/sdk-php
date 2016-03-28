<?php namespace Phonedotcom\Sdk\Models\Event\Log;

use Phonedotcom\Sdk\Models\Event\Log;

class SmsNewInboundEntry extends Log
{
    protected static $singleServiceType = 'sms.new.inbound';

    protected $staticRelationMap = [
        'sms' => 'Phonedotcom\Sdk\Models\Sms'
    ];
}
