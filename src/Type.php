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

use BackedEnum;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use stdClass;

/**
 * Handles value type casting.
 */
final class Type
{
    const ARRAY = 'array';
    const BOOLEAN = 'boolean';
    const DATE = 'date';
    const DATE_TIME = 'datetime';
    const DATE_UNIX = 'date_unix';
    const ENUM = 'enum';
    const FLOAT = 'float';
    const INTEGER = 'integer';
    const OBJECT = 'object';
    const STRING = 'string';

    private static ?Key $encryptionKey = null;

    /**
     * Marshals a value for a given property from storage.
     */
    public static function cast(Property $property, mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        // handle empty strings as null
        if ($property->null && '' === $value) {
            return null;
        }

        // perform decryption, if enabled
        if ($property->encrypted) {
            $value = Crypto::decrypt($value, self::$encryptionKey);
        }

        $type = $property->type;
        if (!$type) {
            return $value;
        }

        if ($type == self::ENUM) {
            return self::to_enum($value, (string) $property->enum_class);
        }

        $m = 'to_'.$property->type;

        return self::$m($value);
    }

    /**
     * Casts a value to a string.
     */
    public static function to_string(mixed $value): string
    {
        return (string) $value;
    }

    /**
     * Casts a value to an integer.
     */
    public static function to_integer(mixed $value): int
    {
        return (int) $value;
    }

    /**
     * Casts a value to a float.
     */
    public static function to_float(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * Casts a value to a boolean.
     */
    public static function to_boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Casts a date value as a UNIX timestamp.
     */
    public static function to_date_unix(mixed $value): int
    {
        if (!is_numeric($value)) {
            return strtotime($value);
        }

        return $value + 0;
    }

    /**
     * Casts a value to an array.
     */
    public static function to_array(mixed $value): array
    {
        // decode JSON strings into an array
        if (is_string($value)) {
            return (array) json_decode($value, true);
        }

        return (array) $value;
    }

    /**
     * Casts a value to an object.
     */
    public static function to_object(mixed $value): stdClass
    {
        // decode JSON strings into an object
        if (is_string($value)) {
            return (object) json_decode($value);
        }

        return (object) $value;
    }

    public static function to_enum(mixed $value, string $enumClass): BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        return $enumClass::from($value);
    }

    /**
     * Sets the encryption key to be used when encryption is enabled for a property.
     */
    public static function setEncryptionKey(Key $key): void
    {
        self::$encryptionKey = $key;
    }

    /**
     * Gets the encryption key, if used.
     */
    public static function getEncryptionKey(): ?Key
    {
        return self::$encryptionKey;
    }
}
