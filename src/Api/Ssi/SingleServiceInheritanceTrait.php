<?php

namespace PhoneCom\Sdk\Api\Ssi;

use PhoneCom\Sdk\Api\Ssi\Exceptions\SingleServiceInheritanceException;
use PhoneCom\Sdk\Api\Ssi\Exceptions\SingleServiceInheritanceInvalidAttributesException;

trait SingleServiceInheritanceTrait
{
    protected static $singleServiceTypeMap = [];

    protected static $allPersisted = [];

    public static function bootSingleServiceInheritanceTrait()
    {
        static::getSingleServiceTypeMap();
        static::getAllPersistedAttributes();
        static::addGlobalScope(new SingleServiceInheritanceScope);
        static::observe(new SingleServiceInheritanceObserver());
    }

    public static function getSingleServiceTypeMap()
    {
        $calledClass = get_called_class();

        if (array_key_exists($calledClass, self::$singleServiceTypeMap)) {
            return self::$singleServiceTypeMap[$calledClass];
        }

        $typeMap = [];

        if (property_exists($calledClass, 'singleServiceType')) {
            $classType = static::$singleServiceType;
            $typeMap[$classType] = $calledClass;
        }

        if (property_exists($calledClass, 'singleServiceSubclasses')) {
            $subclasses = static::$singleServiceSubclasses;
            if (!in_array($calledClass, $subclasses)) {
                foreach ($subclasses as $subclass) {
                    // array_merge() won't work if the singleServiceType values are numeric, so we brute-force it.
                    foreach ($subclass::getSingleServiceTypeMap() as $key => $value) {
                        $typeMap[$key] = $value;
                    }
                }
            }
        }

        self::$singleServiceTypeMap[$calledClass] = $typeMap;

        return $typeMap;
    }

    public static function getAllPersistedAttributes()
    {
        $calledClass = get_called_class();

        if (array_key_exists($calledClass, self::$allPersisted)) {
            return self::$allPersisted[$calledClass];

        } else {
            $persisted = [];
            if (property_exists($calledClass, 'persisted')) {
                $persisted  = $calledClass::$persisted;
            }

            $parent = get_parent_class($calledClass);
            if (method_exists($parent, 'getAllPersistedAttributes')) {
                $persisted = array_merge($persisted, $parent::getAllPersistedAttributes());
            }
        }

        self::$allPersisted[$calledClass] = $persisted;

        return self::$allPersisted[$calledClass];
    }

    public function getPersistedAttributes()
    {
        $persisted = static::getAllPersistedAttributes();
        if (empty($persisted)) {
            return [];
        }

        return array_merge(
            [$this->primaryKey, static::$singleServiceTypeField],
            static::getAllPersistedAttributes(),
            $this->getDates()
        );
    }

    public function filterPersistedAttributes()
    {
        $persisted = $this->getPersistedAttributes();
        $extraAttributes = null;
        if (!empty($persisted)) {
            $extraAttributes = array_diff(array_keys($this->attributes), $this->getPersistedAttributes());

            if (!empty($extraAttributes)) {
                if ($this->getThrowInvalidAttributeExceptions()) {
                    throw new SingleServiceInheritanceInvalidAttributesException(
                        "Cannot save " . get_called_class() . ".",
                        $extraAttributes
                    );
                }

                foreach ($extraAttributes as $attribute) {
                    unset($this->attributes[$attribute]);
                }
            }
        }
    }

    public function getSingleServiceTypes()
    {
        return array_keys(static::getSingleServiceTypeMap());
    }

    public function setSingleServiceType()
    {
        $modelClass = get_class($this);
        $classType = (property_exists($modelClass, 'singleServiceType') ? $modelClass::$singleServiceType : null);
        if (!$classType) {
            throw new SingleServiceInheritanceException(
                'Cannot save Single service inheritance model without declaring static property $singleServiceType.'
            );
        }

        $this->{static::$singleServiceTypeField} = $classType;
    }

    public function newFromBuilder($attributes = array())
    {
        $typeField = static::$singleServiceTypeField;

        if (!isset($attributes->$typeField)) {
            throw new SingleServiceInheritanceException(
                "Cannot construct newFromBuilder without a value for $typeField"
            );
        }

        $classType = $attributes->$typeField;
        $childTypes = static::getSingleServiceTypeMap();

        if (!array_key_exists($classType, $childTypes)) {
            throw new SingleServiceInheritanceException(
                "Cannot construct newFromBuilder for unrecognized $typeField=$classType"
            );
        }

        $class = $childTypes[$classType];
        $instance = (new $class)->newInstance([], true);
        $instance->setFilteredAttributes((array) $attributes);

        return $instance;
    }

    public function getQualifiedSingleServiceTypeColumn()
    {
        return static::$singleServiceTypeField;
    }

    public function setFilteredAttributes(array $attributes)
    {
        $persistedAttributes = $this->getPersistedAttributes();
        if (empty($persistedAttributes)) {
            $filteredAttributes = $attributes;

        } else {
            $extraAttributes = array_filter(
                array_diff_key($attributes, array_flip($persistedAttributes)),
                function ($value) {
                    return !is_null($value);
                }
            );

            if (!empty($extraAttributes) && $this->getThrowInvalidAttributeExceptions()) {
                throw new SingleServiceInheritanceInvalidAttributesException(
                    "Cannot construct " . get_called_class() . ".",
                    $extraAttributes
                );
            }

            $filteredAttributes = array_intersect_key($attributes, array_flip($persistedAttributes));
        }

        $this->setRawAttributes($filteredAttributes, true);
    }

    protected function getThrowInvalidAttributeExceptions()
    {
        $exists = property_exists(get_called_class(), 'throwInvalidAttributeExceptions');

        return ($exists ? static::$throwInvalidAttributeExceptions : false);
    }
}
