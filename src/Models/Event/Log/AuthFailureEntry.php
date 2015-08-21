<?php namespace PhoneCom\Sdk\Models\Event\Log;

use PhoneCom\Sdk\Models\Event\Log;

class AuthFailureEntry extends Log
{
    protected static $singleServiceType = 'auth.failure';

    protected $staticRelationMap = [
        'application' => 'PhoneCom\Sdk\Models\Application'
    ];
}
