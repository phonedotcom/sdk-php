<?php

namespace PhoneCom\Sdk\Query\Grammars;

use PhoneCom\Sdk\Query\Builder;
use PhoneCom\Sdk\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [];


    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param  \PhoneCom\Sdk\Query\Builder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  \PhoneCom\Sdk\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        // TODO: Implement this!!!
        throw new \Exception(__METHOD__ . ' not implemented yet');

        return ['truncate '.$this->wrapTable($query->from) => []];
    }
}
