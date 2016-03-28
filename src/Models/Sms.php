<?php namespace Phonedotcom\Sdk\Models;

use Phonedotcom\Sdk\Api\Eloquent\Model;

class Sms extends Model
{
    protected $pathInfo = '/sms';
    protected $insertReturnsCollection = true;
    protected $dates = ['created', 'scheduled'];
}
