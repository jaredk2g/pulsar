<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests;

use DateTimeImmutable;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Exception\ModelException;
use Pulsar\Property;
use Pulsar\Tests\Enums\TestEnumInteger;
use Pulsar\Tests\Enums\TestEnumString;
use Pulsar\Type;
use stdClass;
use ValueError;

class TypeTest extends MockeryTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        date_default_timezone_set('UTC');
    }

    public function testCast(): void
    {
        $property = new Property(null: true);
        $this->assertNull(Type::cast($property, ''));
        $this->assertTrue(false === Type::cast($property, false));

        $property = new Property(type: Type::STRING, null: false);
        $this->assertEquals('string', Type::cast($property, 'string'));
        $this->assertNull(Type::cast($property, null));

        $property = new Property(type: Type::BOOLEAN, null: false);
        $this->assertTrue(Type::cast($property, true));
        $this->assertTrue(Type::cast($property, '1'));
        $this->assertFalse(Type::cast($property, false));

        $property = new Property(type: Type::INTEGER, null: false);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));

        $property = new Property(type: Type::FLOAT, null: false);
        $this->assertEquals(1.23, Type::cast($property, 1.23));
        $this->assertEquals(123.0, Type::cast($property, '123'));

        $property = new Property(type: Type::INTEGER, null: false);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));

        $property = new Property(type: Type::DATE, null: false);
        $this->assertEquals(new DateTimeImmutable('2023-01-08'), Type::cast($property, '2023-01-08'));
        $this->assertEquals(new DateTimeImmutable('2023-01-08'), Type::cast($property, new DateTimeImmutable('2023-01-08')));

        $property = new Property(type: Type::DATETIME, null: false);
        $this->assertEquals(new DateTimeImmutable('2010-01-28T17:00:00'), Type::cast($property, '2010-01-28 17:00:00'));
        $this->assertEquals(new DateTimeImmutable('2010-01-28T17:00:00'), Type::cast($property, new DateTimeImmutable('2010-01-28T17:00:00')));

        $property = new Property(type: Type::DATE_UNIX, null: false);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Type::cast($property, 'Aug-20-2015'));

        $property = new Property(type: Type::ARRAY, null: false);
        $this->assertEquals(['test' => true], Type::cast($property, '{"test":true}'));
        $this->assertEquals(['test' => true], Type::cast($property, ['test' => true]));

        $property = new Property(type: Type::OBJECT, null: false);
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Type::cast($property, '{"test":true}'));
        $this->assertEquals($expected, Type::cast($property, $expected));

        $property = new Property(type: Type::ENUM, null: false, enum_class: TestEnumString::class);
        $this->assertEquals(TestEnumString::First, Type::cast($property, 'first'));
        $this->assertEquals(TestEnumString::First, Type::cast($property, TestEnumString::First));
    }

    public function testCastEncrypted(): void
    {
        $key = Key::loadFromAsciiSafeString('def000008c6cd2d9a56c128d08773b38fe685c710f2bb7be08cc109c0841df42e8a9ed5995ac5f28354ff2ffaedffc9dd6d06bd6890fd12e44bef48c48b7a8a4bd94fe75');
        Type::setEncryptionKey($key);

        $property = new Property(encrypted: true, null: true);
        $this->assertNull(Type::cast($property, null));
        $this->assertNull(Type::cast($property, ''));

        $encrypted = Crypto::encrypt('original value', $key);
        $decrypted = Type::cast($property, $encrypted);
        $this->assertEquals('original value', $decrypted);

        $property = new Property(type: Type::OBJECT, encrypted: true);
        $encrypted = Crypto::encrypt('{"test":true}', $key);
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Type::cast($property, $encrypted));
    }

    public function testToString(): void
    {
        $this->assertEquals('string', Type::to_string('string'));
        $this->assertEquals('123', Type::to_string(123));
    }

    public function testToInteger(): void
    {
        $this->assertEquals(123, Type::to_integer(123));
        $this->assertEquals(123, Type::to_integer('123'));
    }

    public function testToFloat(): void
    {
        $this->assertEquals(1.23, Type::to_float(1.23));
        $this->assertEquals(123.0, Type::to_float('123'));
    }

    public function testToBoolean(): void
    {
        $this->assertTrue(Type::to_boolean(true));
        $this->assertTrue(Type::to_boolean('1'));
        $this->assertFalse(Type::to_boolean(false));
    }

    public function testToDate(): void
    {
        $this->assertEquals(new DateTimeImmutable('2023-01-08'), Type::to_date('2023-01-08', null));
        $this->assertEquals(new DateTimeImmutable('2023-01-08'), Type::to_date(new DateTimeImmutable('2023-01-08'), null));
        $this->assertEquals(new DateTimeImmutable('2015-08-20'), Type::to_date('Aug-20-2015', 'M-j-Y'));
    }

    public function testToDateInvalid(): void
    {
        $this->expectException(ModelException::class);
        Type::to_date('not valid', null);
    }

    public function testToDateTime(): void
    {
        $this->assertEquals(new DateTimeImmutable('2023-01-08T01:02:03'), Type::to_datetime('2023-01-08 01:02:03', null));
        $this->assertEquals(new DateTimeImmutable('2023-01-08T01:02:03'), Type::to_datetime(new DateTimeImmutable('2023-01-08 01:02:03'), null));
        $this->assertEquals(new DateTimeImmutable('2015-08-20T01:02:03'), Type::to_datetime('Aug-20-2015 01:02:03', 'M-j-Y h:i:s'));
    }

    public function testToDateTimeInvalid(): void
    {
        $this->expectException(ModelException::class);
        Type::to_datetime('not valid', null);
    }

    public function testToDateUnix(): void
    {
        $this->assertEquals(123, Type::to_date_unix(123));
        $this->assertEquals(123, Type::to_date_unix('123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Type::to_date_unix('Aug-20-2015'));
    }

    public function testToArray(): void
    {
        $this->assertEquals(['test' => true], Type::to_array('{"test":true}'));
        $this->assertEquals(['test' => true], Type::to_array(['test' => true]));
        $this->assertEquals([], Type::to_array(''));
        $this->assertEquals([], Type::to_array(null));
    }

    public function testToObject(): void
    {
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Type::to_object('{"test":true}'));
        $this->assertEquals($expected, Type::to_object($expected));

        $expected = new stdClass();
        $this->assertEquals($expected, Type::to_object(''));
        $this->assertEquals($expected, Type::to_object(null));
    }

    public function testToEnumString(): void
    {
        $this->assertEquals(TestEnumString::First, Type::to_enum('first', TestEnumString::class));
        $this->assertEquals(TestEnumString::Second, Type::to_enum('second', TestEnumString::class));
        $this->assertEquals(TestEnumString::Third, Type::to_enum('third', TestEnumString::class));
    }

    public function testToEnumInteger(): void
    {
        $this->assertEquals(TestEnumInteger::First, Type::to_enum(1, TestEnumInteger::class));
        $this->assertEquals(TestEnumInteger::Second, Type::to_enum(2, TestEnumInteger::class));
        $this->assertEquals(TestEnumInteger::Third, Type::to_enum(3, TestEnumInteger::class));
    }

    public function testToEnumInvalid(): void
    {
        $this->expectException(ValueError::class);
        Type::to_enum('not valid', TestEnumString::class);
    }
}
