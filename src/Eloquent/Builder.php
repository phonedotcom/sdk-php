<?php

namespace PhoneCom\Sdk\Eloquent;

use Closure;
use PhoneCom\Sdk\Query\Builder as QueryBuilder;

class Builder
{
    /**
     * The base query builder instance.
     *
     * @var \PhoneCom\Sdk\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \PhoneCom\Sdk\Eloquent\Model
     */
    protected $model;

    /**
     * A replacement for the typical delete function.
     *
     * @var \Closure
     */
    protected $onDelete;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getVerb', 'getUrl', 'getOptions',
        'exists', 'count',
    ];

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \PhoneCom\Sdk\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Model|\PhoneCom\Sdk\Eloquent\Collection|null
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id)) {
            return $this->findMany($id, $columns);
        }

        $this->query->where($this->model->getQualifiedKeyName(), 'eq', $id);

        return $this->first($columns);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  array  $ids
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        if (empty($ids)) {
            return $this->model->newCollection();
        }

        $this->query->whereIn($this->model->getQualifiedKeyName(), $ids);

        return $this->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Model|\PhoneCom\Sdk\Eloquent\Collection
     *
     * @throws \PhoneCom\Sdk\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->find($id, $columns);

        if (is_array($id)) {
            if (count($result) == count(array_unique($id))) {
                return $result;
            }
        } elseif (!is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Model|static|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Model|static
     *
     * @throws \PhoneCom\Sdk\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        if (!is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $models = $this->getModels($columns);

        return $this->model->newCollection($models);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->first([$column]);

        if ($result) {
            return $result->{$column};
        }
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return void
     */
    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();

        while (count($results) > 0) {
            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if (call_user_func($callback, $results) === false) {
                break;
            }

            $page++;

            $results = $this->forPage($page, $count)->get();
        }
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->query->update($values);
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }

        return $this->query->delete();
    }

    /**
     * Run the default delete function on the builder.
     *
     * @return mixed
     */
    public function forceDelete()
    {
        return $this->query->delete();
    }

    /**
     * Register a replacement for the default delete function.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function onDelete(Closure $callback)
    {
        $this->onDelete = $callback;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     * @return \PhoneCom\Sdk\Eloquent\Model[]
     */
    public function getModels($columns = ['*'])
    {
        $results = $this->query->get($columns);

        $connection = $this->model->getConnectionName();

        return $this->model->hydrate($results, $connection)->all();
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        call_user_func_array([$this->query, 'where'], func_get_args());

        return $this;
    }

    /**
     * Call the given model scope on the underlying model.
     *
     * @param  string  $scope
     * @param  array   $parameters
     * @return \PhoneCom\Sdk\Query\Builder
     */
    protected function callScope($scope, $parameters)
    {
        array_unshift($parameters, $this);

        return call_user_func_array([$this->model, $scope], $parameters) ?: $this;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \PhoneCom\Sdk\Query\Builder|static
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \PhoneCom\Sdk\Query\Builder  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \PhoneCom\Sdk\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \PhoneCom\Sdk\Eloquent\Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->from($model->getPathInfo(), $model->getPathParams());

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->model, $scope = 'scope'.ucfirst($method))) {
            return $this->callScope($scope, $parameters);
        }

        $result = call_user_func_array([$this->query, $method], $parameters);

        return in_array($method, $this->passthru) ? $result : $this;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
