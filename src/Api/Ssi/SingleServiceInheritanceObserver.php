<?php

namespace Phonedotcom\Sdk\Api\Ssi;

class SingleServiceInheritanceObserver
{
    public function saving($model)
    {
        $model->filterPersistedAttributes();
        $model->setSingleServiceType();
    }
}
