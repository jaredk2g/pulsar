<?php

namespace Pulsar\Tests\Driver;

use DateTimeImmutable;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Property;
use Pulsar\Tests\Enums\TestEnumInteger;
use Pulsar\Tests\Enums\TestEnumString;
use Pulsar\Type;
use stdClass;

trait SerializeValueTestTrait
{
    public function testSerializeValueString(): void
    {
        $driver = $this->getDriver();
        $this->assertEquals('string', $driver->serializeValue('string', null));
    }

    public function testSerializeValueArray(): void
    {
        $driver = $this->getDriver();
        $arr = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($arr, null));
    }

    public function testSerializeValueObject(): void
    {
        $driver = $this->getDriver();
        $obj = new stdClass();
        $obj->test = true;
        $this->assertEquals('{"test":true}', $driver->serializeValue($obj, null));
    }

    public function testSerializeValueEnum(): void
    {
        $driver = $this->getDriver();
        $this->assertEquals('first', $driver->serializeValue(TestEnumString::First, null));
        $this->assertEquals(1, $driver->serializeValue(TestEnumInteger::First, null));
    }

    public function testSerializeValueDateTime(): void
    {
        date_default_timezone_set('UTC');
        $driver = $this->getDriver();
        $this->assertEquals('2023-01-08 01:02:03', $driver->serializeValue(new DateTimeImmutable('2023-01-08 01:02:03'), null));
        $this->assertEquals('2023-01-08', $driver->serializeValue(new DateTimeImmutable('2023-01-08'), new Property(type: Type::DATE)));
        $this->assertEquals('2023-01-08 01:02:03', $driver->serializeValue(new DateTimeImmutable('2023-01-08 01:02:03'), new Property(type: Type::DATETIME)));
        $this->assertEquals('1673139723', $driver->serializeValue(new DateTimeImmutable('2023-01-08 01:02:03'), new Property(date_format: 'U')));
    }
}