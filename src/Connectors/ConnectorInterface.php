<?php

namespace PhoneCom\Sdk\Connectors;

interface ConnectorInterface
{
    /**
     * TODO: What to return here???
     *
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config);
}
