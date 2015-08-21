<?php

namespace PhoneCom\Sdk\Api\Ssi;

use PhoneCom\Sdk\Api\Eloquent\Builder;
use PhoneCom\Sdk\Api\Eloquent\Model;
use PhoneCom\Sdk\Api\Eloquent\ScopeInterface;

class SingleServiceInheritanceScope implements ScopeInterface
{
    public function apply(Builder $builder, Model $model)
    {
        $subclassTypes = array_keys($model->getSingleServiceTypeMap());

        if (!empty($subclassTypes)) {
            $builder->whereIn($model->getQualifiedSingleServiceTypeColumn(), $subclassTypes);
        }
    }

    public function remove(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedSingleServiceTypeColumn();

        $query = $builder->getQuery();

        $bindings = $query->getRawBindings()['where'];
        foreach ((array) $query->wheres as $key => $where) {
            if ($this->isSingleServiceInheritanceConstraint($where, $column)) {
                unset($query->wheres[$key]);

                foreach ($where['values'] as $value) {
                    if (($binding_key = array_search($value, $bindings)) >= 0) {
                        unset($bindings[$binding_key]);
                    }
                }

                $query->setBindings(array_values($bindings));
                $query->wheres = array_values($query->wheres);
            }
        }
    }

    protected function isSingleServiceInheritanceConstraint(array $where, $column)
    {
        return $where['type'] == 'In' && $where['column'] == $column;
    }
}
