<?php namespace PhoneCom\Sdk\Models;

use PhoneCom\Sdk\Api\Eloquent\Model;

class Sms extends Model
{
    protected $pathInfo = '/sms';
    protected $insertReturnsCollection = true;
    protected $dates = ['created', 'scheduled'];
}
