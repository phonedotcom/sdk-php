<?php namespace Phonedotcom\Sdk\Models\Event\Log;

use Phonedotcom\Sdk\Models\Event\Log;

class ListenerFailureEntry extends Log
{
    protected static $singleServiceType = 'listener.failure';

    protected $staticRelationMap = [
        'listener' => 'Phonedotcom\Sdk\Models\Event\Listener'
    ];
}
