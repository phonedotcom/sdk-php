<?php namespace PhoneCom\Sdk\Query;

use PhoneCom\Sdk\ConnectionInterface;
use PhoneCom\Sdk\Query\Exception as QueryException;
use PhoneCom\Sdk\Query\Grammars\Grammar;
use PhoneCom\Sdk\Query\Processors\Processor;

class Builder
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Grammar
     */
    protected $grammar;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * The current query value bindings.
     */
    protected $bindings = [
        'select' => [],
        //'join'   => [],
        'where'  => [],
        //'having' => [],
        'order'  => [],
    ];

    public $headers;

    /**
     * The columns that should be returned.
     */
    public $columns;

    /**
     * The service which the query is targeting.
     */
    public $from;

    /**
     * The where constraints for the query.
     */
    public $wheres;

    /**
     * The orderings for the query.
     */
    public $orders;

    /**
     * The maximum number of records to return.
     */
    public $limit;

    /**
     * The number of records to skip.
     */
    public $offset;

    /**
     * The field backups currently in use.
     *
     * @var array
     */
    protected $backups = [];

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        // zero-argument
        'empty', 'not-empty',

        // one-argument
        'eq', 'ne', 'lt', 'gt', 'lte', 'gte',
        'starts-with', 'ends-with', 'contains', 'not-starts-with', 'not-ends-with', 'not-contains',

        // two-argument
        'between', 'not-between'
    ];

    public function __construct(ConnectionInterface $connection, Grammar $grammar, Processor $processor)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
        $this->processor = $processor;
    }

    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();

        $this->columns = array_merge((array) $this->columns, $column);

        return $this;
    }

    public function from($pathInfo, array $params = [])
    {
        $this->from = [$pathInfo, $params];

        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, 'eq', $value);
            }
        }

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';

        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        $this->wheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Determine if the given operator and value combination is legal.
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);

        return $isOperator && $operator != 'eq' && is_null($value);
    }

    /**
     * Add a where between statement to the query.
     */
    public function whereBetween($column, array $values, $not = false)
    {
        return $this->where($column, 'between', $values);
    }

    /**
     * Add a where not between statement to the query.
     */
    public function whereNotBetween($column, array $values)
    {
        return $this->where($column, 'not-between', $values);
    }

    /**
     * Add an "order by" clause to the query.
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';

        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Set the "offset" value of the query.
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     */
    public function limit($value)
    {
        if ($value > 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the limit and offset for a given page.
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Execute a query for a single record by ID.
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', 'eq', $id)->first($columns);
    }

    /**
     * Get a single column's value from the first result of a query.
     */
    public function value($column)
    {
        $result = (array) $this->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result.
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? reset($results) : null;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        return $this->getFresh($columns);
    }

    /**
     * Execute the query as a fresh "select" statement.
     */
    public function getFresh($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        return $this->processor->processSelect($this, $this->runSelect());
    }

    /**
     * Run the query as a "select" statement against the connection.
     */
    protected function runSelect()
    {
        list($url, $options) = $this->grammar->compileSelect($this);

        return $this->connection->select($url, $options);
    }

    /**
     * Chunk the results of the query.
     */
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

    /**
     * Determine if any rows exist for the current query.
     */
    public function exists()
    {
        $limit = $this->limit;

        $result = $this->limit(1)->count() > 0;

        $this->limit($limit);

        return $result;
    }

    /**
     * Retrieve the "count" result of the query.
     */
    public function count()
    {
        $this->limit(1);

        return $this->processor->processCount($this, $this->runSelect());
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];

        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        foreach ($values as $row) {
            list($url, $options) = $this->grammar->compileInsert($this, $row);

            $this->connection->insert($url, $options);
        }

        return true;
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        list($url, $options) = $this->grammar->compileInsertGetId($this, $values, $sequence);

        return $this->processor->processInsertGetId($this, $url, $options, $values, $sequence);
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $chunkSize = 50;

        $this->chunk($chunkSize, function ($rows) use ($values) {
            foreach ($rows as $existing) {
                list($url, $options) = $this->grammar->compileUpdate($this, $existing, $values);
                $this->connection->update($url, $options);
            }
        });

        return true;
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where('id', 'eq', $id);
        }

        $chunkSize = 50;

        $this->chunk($chunkSize, function ($rows) {
            foreach ($rows as $existing) {
                list($url, $options) = $this->grammar->compileDelete($this, $existing);
                $this->connection->delete($url, $options);
            }
        });

        return true;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return static
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Get the database connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
     *
     * @return \PhoneCom\Sdk\Query\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Get the query grammar instance.
     *
     * @return \PhoneCom\Sdk\Query\Grammars\Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }






/*


    public function removeMasonProperties(\stdClass $data)
    {
        foreach ($data as $property => $value) {
            if (substr($property, 0, 1) == '@') {
                unset($data->{$property});
                continue;
            }

            if (is_array($value)) {
                $this->removeMasonPropertiesFromArray($value);

            } elseif (is_object($value)) {
                $this->removeMasonProperties($value);
            }
        }

        return $data;
    }

    private function removeMasonPropertiesFromArray(array &$value)
    {
        foreach ($value as $index => $subvalue) {
            if (is_array($subvalue)) {
                $this->removeMasonPropertiesFromArray($subvalue);

            } elseif (is_object($subvalue)) {
                $this->removeMasonProperties($subvalue);
            }
        }
    }
*/
}
