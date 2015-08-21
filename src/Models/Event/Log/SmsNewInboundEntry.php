<?php namespace PhoneCom\Sdk\Models\Event\Log;

use PhoneCom\Sdk\Models\Event\Log;

class SmsNewInboundEntry extends Log
{
    protected static $singleServiceType = 'sms.new.inbound';

    protected $staticRelationMap = [
        'sms' => 'PhoneCom\Sdk\Models\Sms'
    ];
}
