<?php namespace PhoneCom\Sdk;

use PhoneCom\Sdk\Models\Model;

class Sms extends Model
{
    protected $pathInfo = '/sms';

    /**
     * @return array List of messages created
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        return $model->save();
    }

    public function save()
    {
        if (empty($this->attributes['id'])) {
            return $this->hydrate($this->newQuery()->insert($this->attributes)[0]->items);
        }

        return $this->newQuery()->where('id', 'eq', $this->attributes['id'])->update($this->attributes);
    }
}
