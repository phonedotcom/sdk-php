<?php

namespace PhoneCom\Sdk;

use Closure;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * @return \PhoneCom\Sdk\Query\Builder
     */
    public function service($url);

    /**
     * Run a select statement and return a single result.
     *
     * @return mixed
     */
    public function selectOne($url, $options = []);

    /**
     * Run a select statement against the database.
     *
     * @return array
     */
    public function select($url, $options = []);

    /**
     * Run an insert statement against the database.
     *
     * @return bool
     */
    public function insert($url, $options = []);

    /**
     * Run an update statement against the database.
     *
     * @return int
     */
    public function update($url, $options = []);

    /**
     * Run a delete statement against the database.
     *
     * @return int
     */
    public function delete($url, $options = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @return bool
     */
    public function statement($verb, $url, $options = []);

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($verb, $url, $options = []);
}
