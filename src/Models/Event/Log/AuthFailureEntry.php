<?php namespace Phonedotcom\Sdk\Models\Event\Log;

use Phonedotcom\Sdk\Models\Event\Log;

class AuthFailureEntry extends Log
{
    protected static $singleServiceType = 'auth.failure';

    protected $staticRelationMap = [
        'application' => 'Phonedotcom\Sdk\Models\Application'
    ];
}
