<?php namespace PhoneCom\Sdk\Models;

class ModelQueryBuilder
{
    /**
     * @var \PhoneCom\Sdk\QueryBuilder
     */
    protected $query;

    /**
     * @var \PhoneCom\Sdk\Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getVerb', 'getUrl', 'getOptions',
        'exists', 'count',
    ];

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * @return \PhoneCom\Sdk\Model|array|null
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
     * @return \PhoneCom\Sdk\Model|array
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

        throw (new NotFoundException)->setModel(get_class($this->model));
    }

    public function first()
    {
        return $this->take(1)->get()->first();
    }

    public function firstOrFail()
    {
        if (!is_null($model = $this->first())) {
            return $model;
        }

        throw (new NotFoundException)->setModel(get_class($this->model));
    }

    public function get()
    {
        $results = $this->query->get();

        return $this->model->hydrate($results);
    }

    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();

        while (count($results) > 0) {
            if (call_user_func($callback, $results) === false) {
                break;
            }

            $page++;

            $results = $this->forPage($page, $count)->get();
        }
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
