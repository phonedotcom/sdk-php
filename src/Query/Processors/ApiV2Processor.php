<?php namespace PhoneCom\Sdk\Query\Processors;

use PhoneCom\Sdk\Query\Builder;

class ApiV2Processor extends Processor
{
    public function processSelect(Builder $query, $results)
    {
        return (array)@$results->items;

        //$this->removeMasonPropertiesFromArray($items);
        //return $items;
    }

    public function processCount(Builder $query, $results)
    {
        return (int)$results->total;
    }
/*
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
*/
}
