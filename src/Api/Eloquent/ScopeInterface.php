<?php

namespace Phonedotcom\Sdk\Api\Eloquent;

interface ScopeInterface
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     *
     * @return void
     */
    public function remove(Builder $builder, Model $model);
}
