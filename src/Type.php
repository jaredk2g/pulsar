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
use DateTimeImmutable;
use DateTimeInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Pulsar\Exception\ModelException;
use stdClass;

/**
 * Handles value type casting.
 */
final class Type
{
    const ARRAY = 'array';
    const BOOLEAN = 'boolean';
    const DATE = 'date';
    const DATETIME = 'datetime';
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

        if (self::ENUM == $type) {
            return self::to_enum($value, (string) $property->enum_class);
        }

        if (self::DATE == $type) {
            return self::to_date($value, $property->date_format);
        }

        if (self::DATETIME == $type) {
            return self::to_datetime($value, $property->date_format);
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
     * Casts a date value as a Date object.
     */
    public static function to_date(mixed $value, ?string $format): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $format = $format ?? 'Y-m-d';
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if (!$date) {
            throw new ModelException('Could not parse date: '.$value);
        }

        return $date->setTime(0, 0);
    }

    /**
     * Casts a datetime value as a Date object.
     */
    public static function to_datetime(mixed $value, ?string $format): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $format = $format ?? 'Y-m-d H:i:s';
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if (!$date) {
            throw new ModelException('Could not parse datetime: '.$value);
        }

        return $date;
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
