<?php namespace PhoneCom\Sdk\Models\Event\Log;

use PhoneCom\Sdk\Models\Event\Log;

class SmsNewOutboundEntry extends Log
{
    protected static $singleServiceType = 'sms.new.outbound';

    protected $staticRelationMap = [
        'sms' => 'PhoneCom\Sdk\Models\Sms'
    ];
}
