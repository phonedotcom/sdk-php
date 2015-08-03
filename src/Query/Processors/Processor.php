<?php namespace PhoneCom\Sdk\Query\Processors;

use PhoneCom\Sdk\Query\Builder;

class Processor
{
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }

    public function processCount(Builder $query, $results)
    {
        return $results;
    }

    public function processInsertGetId(Builder $query, $url, $options, $values, $sequence = null)
    {
        $item = $query->getConnection()->insert($url, $options);

        $id = $item->{$sequence};

        return is_numeric($id) ? (int) $id : $id;
    }
}
