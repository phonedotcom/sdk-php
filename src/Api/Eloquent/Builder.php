<?php namespace Phonedotcom\Sdk\Api\Eloquent;

use Phonedotcom\Sdk\Api\Query\Builder as QueryBuilder;

class Builder
{
    /**
     * @var \Phonedotcom\Sdk\Api\Query\Builder
     */
    protected $query;

    /**
     * @var \Phonedotcom\Sdk\Api\Eloquent\Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getVerb', 'getUrl', 'getOptions', 'exists', 'count',
    ];

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * @return \Phonedotcom\Sdk\Api\Model|array|null
     */
    public function find($id)
    {
        if (is_array($id)) {
            return $this->findMany($id);
        }

        $this->query->where('id', 'eq', $id);

        return $this->first();
    }

    public function findMany($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $this->query->whereIn('id', $ids);

        return $this->get();
    }

    /**
     * @return \Phonedotcom\Sdk\Api\Model|array
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);

        if (is_array($id)) {
            if (count($result) == count(array_unique($id))) {
                return $result;
            }

        } elseif (!is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    public function first()
    {
        $items = $this->take(1)->get();

        return ($items ? $items->first() : null);
    }

    public function firstOrFail()
    {
        if (!is_null($model = $this->first())) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    public function get()
    {
        $results = $this->query->get();

        return $this->model->hydrate($results);
    }

    public function insertCollection($attributes)
    {
        $results = $this->query->insertCollection($attributes);

        return $this->model->hydrate($results);
    }

    public function getWithTotal()
    {
        list($items, $total) = $this->query->getWithTotal();

        return [$this->model->hydrate($items), $total];
    }

    public function chunk($count, callable $callback)
    {
        $this->limit($count);
        $offset = 0;
        do {
            $results = $this->offset($offset)->get();
            $returnValue = call_user_func($callback, $results);
            $offset += $count;
        } while (count($results) == $count && $returnValue !== false);
    }

    public function update(array $values)
    {
        return $this->query->update($values);
    }

    public function delete()
    {
        return $this->query->delete();
    }

    public function where($column, $operator = null, $value = null)
    {
        call_user_func_array([$this->query, 'where'], func_get_args());

        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->from($model->getPathInfo(), $model->getPathParams());

        return $this;
    }

    public function __call($method, $parameters)
    {
        $result = call_user_func_array([$this->query, $method], $parameters);

        return in_array($method, $this->passthru) ? $result : $this;
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
