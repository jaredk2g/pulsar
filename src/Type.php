<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

/**
 * Handles value type casting.
 */
final class Type
{
    const STRING = 'string';
    const INTEGER = 'integer';
    const FLOAT = 'float';
    const BOOLEAN = 'boolean';
    const DATE = 'date';
    const OBJECT = 'object';
    const ARRAY = 'array';

    /**
     * Marshals a value for a given property from storage.
     *
     * @param mixed $value
     *
     * @return mixed type-casted value
     */
    public static function cast(Property $property, $value)
    {
        if (null === $value) {
            return null;
        }

        // handle empty strings as null
        if ($property->isNullable() && '' === $value) {
            return null;
        }

        $type = $property->getType();
        if (!$type) {
            return $value;
        }

        $m = 'to_'.$property->getType();

        return self::$m($value);
    }

    /**
     * Casts a value to a string.
     *
     * @param mixed $value
     */
    public static function to_string($value): string
    {
        return (string) $value;
    }

    /**
     * Casts a value to an integer.
     *
     * @param mixed $value
     */
    public static function to_integer($value): int
    {
        return (int) $value;
    }

    /**
     * Casts a value to a float.
     *
     * @param mixed $value
     */
    public static function to_float($value): float
    {
        return (float) $value;
    }

    /**
     * Casts a value to a boolean.
     *
     * @param mixed $value
     */
    public static function to_boolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Casts a date value as a UNIX timestamp.
     *
     * @param mixed $value
     */
    public static function to_date($value): int
    {
        if (!is_numeric($value)) {
            return strtotime($value);
        } else {
            return $value + 0;
        }
    }

    /**
     * Casts a value to an array.
     *
     * @param mixed $value
     */
    public static function to_array($value): array
    {
        // decode JSON strings into an array
        if (is_string($value)) {
            return (array) json_decode($value, true);
        }

        return (array) $value;
    }

    /**
     * Casts a value to an object.
     *
     * @param mixed $value
     *
     * @return object
     */
    public static function to_object($value): \stdClass
    {
        // decode JSON strings into an object
        if (is_string($value)) {
            return (object) json_decode($value);
        }

        return (object) $value;
    }
}
