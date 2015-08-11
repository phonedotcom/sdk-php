<?php namespace PhoneCom\Sdk\Models;

use PhoneCom\Sdk\Client;

class QueryBuilder
{
    /**
     * @var Client
     */
    protected $client;

    public $from;

    public $wheres;

    public $orders;

    public $limit;

    public $offset;

    protected $operators = [
        // zero-argument
        'empty', 'not-empty',

        // one-argument
        'eq', 'ne', 'lt', 'gt', 'lte', 'gte',
        'starts-with', 'ends-with', 'contains', 'not-starts-with', 'not-ends-with', 'not-contains',

        // two-argument
        'between', 'not-between'
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function select()
    {
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
            $operator = 'eq';

        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        $this->wheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    public function getCountForPagination()
    {
        return $this->count();
    }

    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);

        return $isOperator && $operator != 'eq' && is_null($value);
    }

    public function whereBetween($column, array $values, $not = false)
    {
        return $this->where($column, 'between', $values);
    }

    public function whereNotBetween($column, array $values)
    {
        return $this->where($column, 'not-between', $values);
    }

    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';

        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    public function skip($value)
    {
        return $this->offset($value);
    }

    public function limit($value)
    {
        if ($value > 0) {
            $this->limit = $value;
        }

        return $this;
    }

    public function take($value)
    {
        return $this->limit($value);
    }

    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    public function find($id)
    {
        return $this->where('id', 'eq', $id)->first();
    }

    public function first()
    {
        $results = $this->take(1)->get();

        return count($results) > 0 ? reset($results) : null;
    }

    public function get()
    {
        $result = $this->runSelect();

        if (empty(@$result->items) || !is_array($result->items)) {
            return [];
        }

        return $result->items;
    }

    public function getWithTotal()
    {
        $result = $this->runSelect();

        return [(array)$result->items, (int)$result->total];
    }

    protected function runSelect()
    {
        $url = $this->compileUrl($this->from[0], @$this->from[1]);

        $options = ['query' => []];

        foreach (['wheres', 'orders', 'limit', 'offset'] as $component) {
            $method = 'compile' . ucfirst($component);
            $this->$method($options['query']);
        }

        return $this->client->select($url, $options);
    }

    public function compileUrl($pathInfo, $params = [])
    {
        $path = $pathInfo;
        if (is_array($params)) {
            foreach ($params as $param => $value) {
                $path = preg_replace("/\{" . preg_quote($param) . "(\:[^\}]+)?\}/", (string)$value, $path);
            }
        }

        return $path;
    }

    protected function compileWheres(array &$options)
    {
        if (!is_null($this->wheres)) {
            $options['filter'] = [];

            foreach ($this->wheres as $where) {
                $string = $where['operator'];

                $value = $where['value'];
                if ($value !== null) {
                    $string .= ':' . (is_scalar($value) ? $value : join(',', $value));
                }

                $options['filter'][$where['column']] = $string;
            }
        }
    }

    protected function compileOrders(array &$options)
    {
        if (!is_null($this->orders)) {
            $options['sort'] = [];

            foreach ($this->orders as $order) {
                $options['sort'][$order['column']] = $order['direction'];
            }
        }
    }

    protected function compileLimit(array &$options)
    {
        if ($this->limit !== null) {
            $options['limit'] = (int)$this->limit;
        }
    }

    protected function compileOffset(array &$options)
    {
        if ($this->offset !== null) {
            $options['offset'] = (int)$this->offset;
        }
    }

    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();

        while (count($results) > 0) {
            if (call_user_func($callback, $results) === false || count($results) !== $count) {
                break;
            }

            $page++;

            $results = $this->forPage($page, $count)->get();
        }
    }

    public function exists()
    {
        $limit = $this->limit;

        $result = ($this->limit(1)->count() > 0);

        $this->limit($limit);

        return $result;
    }

    public function count()
    {
        $result = $this->limit(1)->runSelect();

        return (int)$result->total;
    }

    public function insert(array $values)
    {
        if (empty($values)) {
            return [];
        }

        if (!is_array(reset($values))) {
            $values = [$values];

        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        $responses = [];
        foreach ($values as $row) {
            $url = $this->compileUrl($this->from[0], @$this->from[1]);
            $options = ['json' => $row];

            $responses[] = $this->client->insert($url, $options);
        }

        return $responses;
    }

    public function insertGetId(array $values, $primaryKeyName = 'id')
    {
        $url = $this->compileUrl($this->from[0], @$this->from[1]);
        $options = ['json' => $values];

        $item = $this->client->insert($url, $options);
        $id = $item->{$primaryKeyName};

        return is_numeric($id) ? (int) $id : $id;
    }

    public function update(array $values)
    {
        $chunkSize = 50;

        $this->chunk($chunkSize, function ($rows) use ($values) {
            foreach ($rows as $existing) {
                $url = $existing->{'@controls'}->self->href;

                $newValues = $existing;
                $this->removeMasonPropertiesFromObject($newValues);

                $options = ['json' => array_merge((array)$newValues, $values)];

                $this->client->update($url, $options);
            }
        });

        return true;
    }

    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where('id', 'eq', $id);
        }

        $chunkSize = 50;

        $this->chunk($chunkSize, function ($rows) {
            foreach ($rows as $existing) {
                $url = $existing->{'@controls'}->self->href;
                $this->client->delete($url, []);
            }
        });

        return true;
    }

    public function newQuery()
    {
        return new static($this->client);
    }

    public function getClient()
    {
        return $this->client;
    }

    private function removeMasonPropertiesFromArray(array &$value)
    {
        foreach ($value as $index => $subvalue) {
            if (is_array($subvalue)) {
                $this->removeMasonPropertiesFromArray($subvalue);

            } elseif (is_object($subvalue)) {
                $this->removeMasonPropertiesFromObject($subvalue);
            }
        }
    }

    private function removeMasonPropertiesFromObject(\stdClass $data)
    {
        foreach ($data as $property => $value) {
            if (substr($property, 0, 1) == '@') {
                unset($data->{$property});
                continue;
            }

            if (is_array($value)) {
                $this->removeMasonPropertiesFromArray($value);

            } elseif (is_object($value)) {
                $this->removeMasonPropertiesFromObject($value);
            }
        }

        return $data;
    }
}
