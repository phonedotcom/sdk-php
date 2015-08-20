<?php namespace PhoneCom\Sdk;

use PhoneCom\Sdk\Eloquent\Model;

class Sms extends Model
{
    protected $pathInfo = '/sms';
    protected $insertReturnsCollection = true;
    protected $dates = ['created', 'scheduled'];
}
