<?php

namespace PhoneCom\Sdk;

use PhoneCom\Sdk\Query\Grammars\ApiV2Grammar;
use PhoneCom\Sdk\Query\Processors\ApiV2Processor;

class ApiV2Connection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \PhoneCom\Sdk\Query\Grammars\ApiV2Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new ApiV2Grammar();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \PhoneCom\Sdk\Query\Processors\ApiV2Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new ApiV2Processor();
    }
}
