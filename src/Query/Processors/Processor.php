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

    /*
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }
    */
}
