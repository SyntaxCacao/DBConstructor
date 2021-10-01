<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Validator;
use ReflectionClass;
use ReflectionProperty;

abstract class Type
{
    /** @var bool */
    public $nullable = false;

    public abstract function buildValidator(): Validator;

    /**
     * @throws JsonException
     */
    public function fromJson($json = null)
    {
        if ($json == null) {
            return;
        }

        $array = json_decode($json, true);

        if ($array === false) {
            throw new JsonException();
        }

        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if (isset($array[$property->getName()])) {
                $property->setValue($this, $array[$property->getName()]);
            }
        }
    }

    /**
     * @return string|null null if object is empty
     * @throws JsonException
     */
    public function toJson()
    {
        $array = [];

        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            // As of PHP 8.0, ReflectionProperty::getDefaultValue() is available
            if ($property->getValue($this) !== $class->getDefaultProperties()[$property->getName()]) {
                $array[$property->getName()] = $property->getValue($this);
            }
        }

        if (count($array) == 0) {
            return null;
        }

        $json = json_encode($array);

        if ($json === false) {
            throw new JsonException();
        }

        return $json;
    }
}
