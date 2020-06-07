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

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Type;
use stdClass;

class TypeTest extends MockeryTestCase
{
    public function testCast()
    {
        $property = new Property(['null' => true]);
        $this->assertNull(Type::cast($property, ''));
        $this->assertTrue(false === Type::cast($property, false));

        $property = new Property(['type' => Model::TYPE_STRING, 'null' => false]);
        $this->assertEquals('string', Type::cast($property, 'string'));
        $this->assertNull(Type::cast($property, null));

        $property = new Property(['type' => Model::TYPE_BOOLEAN, 'null' => false]);
        $this->assertTrue(Type::cast($property, true));
        $this->assertTrue(Type::cast($property, '1'));
        $this->assertFalse(Type::cast($property, false));

        $property = new Property(['type' => Model::TYPE_INTEGER, 'null' => false]);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));

        $property = new Property(['type' => Model::TYPE_FLOAT, 'null' => false]);
        $this->assertEquals(1.23, Type::cast($property, 1.23));
        $this->assertEquals(123.0, Type::cast($property, '123'));

        $property = new Property(['type' => Model::TYPE_INTEGER, 'null' => false]);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));

        $property = new Property(['type' => Model::TYPE_DATE, 'null' => false]);
        $this->assertEquals(123, Type::cast($property, 123));
        $this->assertEquals(123, Type::cast($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Type::cast($property, 'Aug-20-2015'));

        $property = new Property(['type' => Model::TYPE_ARRAY, 'null' => false]);
        $this->assertEquals(['test' => true], Type::cast($property, '{"test":true}'));
        $this->assertEquals(['test' => true], Type::cast($property, ['test' => true]));

        $property = new Property(['type' => Model::TYPE_OBJECT, 'null' => false]);
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Type::cast($property, '{"test":true}'));
        $this->assertEquals($expected, Type::cast($property, $expected));
    }

    public function testToString()
    {
        $this->assertEquals('string', Type::to_string('string'));
        $this->assertEquals('123', Type::to_string(123));
    }

    public function testToInteger()
    {
        $this->assertEquals(123, Type::to_integer(123));
        $this->assertEquals(123, Type::to_integer('123'));
    }

    public function testToFloat()
    {
        $this->assertEquals(1.23, Type::to_float(1.23));
        $this->assertEquals(123.0, Type::to_float('123'));
    }

    public function testToBoolean()
    {
        $this->assertTrue(Type::to_boolean(true));
        $this->assertTrue(Type::to_boolean('1'));
        $this->assertFalse(Type::to_boolean(false));
    }

    public function testToDate()
    {
        $this->assertEquals(123, Type::to_date(123));
        $this->assertEquals(123, Type::to_date('123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Type::to_date('Aug-20-2015'));
    }

    public function testToArray()
    {
        $this->assertEquals(['test' => true], Type::to_array('{"test":true}'));
        $this->assertEquals(['test' => true], Type::to_array(['test' => true]));
        $this->assertEquals([], Type::to_array(''));
        $this->assertEquals([], Type::to_array(null));
    }

    public function testToObject()
    {
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Type::to_object('{"test":true}'));
        $this->assertEquals($expected, Type::to_object($expected));

        $expected = new stdClass();
        $this->assertEquals($expected, Type::to_object(''));
        $this->assertEquals($expected, Type::to_object(null));
    }
}
