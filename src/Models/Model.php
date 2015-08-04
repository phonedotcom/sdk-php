<?php namespace PhoneCom\Sdk\Models;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as BaseCollection;

abstract class Model implements ArrayAccess, Arrayable
{
    /**
     * @var Client
     */
    private static $client;

    protected $pathInfo;
    protected $pathParams = [];

    protected $attributes = [];

    public static function setClient(Client $client)
    {
        self::$client = $client;
    }

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function newInstance($attributes = [])
    {
        return new static((array)$attributes);
    }

    public static function hydrate(array $items)
    {
        $output = [];
        foreach ($items as $attributes) {
            $output[] = new static((array)$attributes);
        }

        return $output;
    }

    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        $model->save();

        return $model;
    }

    public static function firstOrCreate(array $attributes)
    {
        if (!is_null($instance = static::where($attributes)->first())) {
            return $instance;
        }

        return static::create($attributes);
    }

    public static function firstOrNew(array $attributes)
    {
        if (!is_null($instance = static::where($attributes)->first())) {
            return $instance;
        }

        return new static($attributes);
    }

    public static function updateOrCreate(array $attributes, array $values = [])
    {
        $instance = static::firstOrNew($attributes);

        $instance->fill($values)->save();

        return $instance;
    }

    public static function query()
    {
        return (new static)->newQuery();
    }

    public static function all()
    {
        return static::query()->get();
    }

    public static function findOrNew($id)
    {
        if (!is_null($model = static::find($id))) {
            return $model;
        }

        return new static;
    }

    public static function destroy($ids)
    {
        $count = 0;

        $ids = (is_array($ids) ? $ids : func_get_args());

        $instance = new static;

        foreach ($instance->whereIn('id', $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    public function delete()
    {
        $this->newQuery()->where('id', 'eq', $this->attributes['id'])->delete();
    }

    public function update(array $attributes = [])
    {
        return $this->newQuery()->update($attributes);
    }

    public function save()
    {
        if (!empty($this->attributes['id'])) {
            $this->newQuery()->where('id', 'eq', $this->attributes['id'])->update($this->attributes);

        } else {
            $this->newQuery()->insert($this->attributes);
        }

        return true;
    }

    public function newQuery()
    {
        $query = new QueryBuilder(self::$client);

        return (new ModelQueryBuilder($query))->setModel($this);
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function getAttribute($key)
    {
        $method = 'get' . Str::studly($key) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method};
        }

        return @$this->attributes[$key];
    }

    public function setAttribute($key, $value)
    {
        $method = 'set'.Str::studly($key).'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method}($value);
        }

        $this->attributes[$key] = $value;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public function getPathInfo()
    {
        return $this->pathInfo;
    }

    public function getPathParams()
    {
        return $this->pathParams;
    }

    public function setPathParams(array $params)
    {
        $this->pathParams = $params;

        return $this;
    }

    public function __isset($key)
    {
        $method = 'get' . Str::studly($key) . 'Attribute';

        return (@$this->attributes[$key] || method_exists($this, $method));
    }

    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    public function __call($method, $parameters)
    {
        $query = $this->newQuery();

        return call_user_func_array([$query, $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }
}