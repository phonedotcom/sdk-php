<?php

namespace PhoneCom\Sdk\Query\Grammars;

use PhoneCom\Sdk\Query\Builder;

class ApiV2Grammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        //'aggregate',
        //'columns',
        //'from',
        'wheres',
        'orders',
        'limit',
        'offset'
    ];

    /**
     * Compile a select query into SQL.
     */
    public function compileSelect(Builder $query)
    {
        $url = $this->compileUrl($query->from[0], @$query->from[1]);

        $options = ['query' => []];

        foreach ($this->selectComponents as $component) {
            if (!is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $options['query'] = array_merge($options['query'], $this->$method($query, $query->$component));
            }
        }

        return [$url, $options];
    }

    /**
     * Compile an insert statement into SQL.
     */
    public function compileInsert(Builder $query, array $values)
    {
        $url = $this->compileUrl($query->from[0], @$query->from[1]);
        $options = ['json' => $values];

        return [$url, $options];
    }

    /**
     * Compile an insert and get ID statement into SQL.
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param  \PhoneCom\Sdk\Query\Builder  $query
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        $params = [];

        if (!is_null($query->wheres)) {
            $params['filter'] = [];

            foreach ($query->wheres as $where) {
                $string = $where['operator'];

                $value = $where['value'];
                if ($value !== null) {
                    $string .= ':' . (is_scalar($value) ? $value : join(',', $value));
                }

                $params['filter'][$where['column']] = $string;
            }
        }

        return $params;
    }

    /**
     * Compile the "order by" portions of the query.
     */
    protected function compileOrders(Builder $query, $orders)
    {
        $params = [];

        if (!is_null($query->orders)) {
            $params['sort'] = [];

            foreach ($query->orders as $order) {
                $params['sort'][$order['column']] = $order['direction'];
            }
        }

        return $params;
    }

    /**
     * Compile the "limit" portions of the query.
     */
    protected function compileLimit(Builder $query, $limit)
    {
        $params = [];
        if ($query->limit !== null) {
            $params['limit'] = (int)$query->limit;
        }

        return $params;
    }

    /**
     * Compile the "offset" portions of the query.
     */
    protected function compileOffset(Builder $query, $offset)
    {
        $params = [];
        if ($query->offset !== null) {
            $params['offset'] = (int)$query->offset;
        }

        return $params;
    }

    /**
     * Compile an update statement into SQL.
     */
    public function compileUpdate(Builder $query, $existing, $values)
    {
        $url = $existing->{'@controls'}->self->href;

        $newValues = $existing;
        $this->removeMasonPropertiesFromObject($newValues);

        $options = ['json' => array_merge((array)$newValues, $values)];

        return [$url, $options];
    }

    /**
     * Compile a delete statement into SQL.
     */
    public function compileDelete(Builder $query, $existing)
    {
        $url = $existing->{'@controls'}->self->href;

        return [$url, []];
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
