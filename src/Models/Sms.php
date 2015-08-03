<?php namespace PhoneCom\Sdk\Models;

use PhoneCom\Sdk\Eloquent\Model;
use PhoneCom\Sdk\QueryException;
use PhoneCom\Sdk\Query\Builder;

class Sms extends Model
{
    protected $pathInfo = '/sms';

    protected function insertAndSetId(Builder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    // TODO: Sigh.  Sms is difficult to INSERT because it returns a collection object.
    // TODO: How about making the API return a single item if only one "to" was given, or a collection if otherwise?
}
