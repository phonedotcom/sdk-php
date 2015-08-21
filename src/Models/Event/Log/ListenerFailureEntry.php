<?php namespace PhoneCom\Sdk\Models\Event\Log;

use PhoneCom\Sdk\Models\Event\Log;

class ListenerFailureEntry extends Log
{
    protected static $singleServiceType = 'listener.failure';

    protected $staticRelationMap = [
        'listener' => 'PhoneCom\Sdk\Models\Event\Listener'
    ];
}
