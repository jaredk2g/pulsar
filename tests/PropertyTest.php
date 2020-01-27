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
use Pulsar\Property;
use stdClass;

class PropertyTest extends MockeryTestCase
{
    public function testToString()
    {
        $this->assertEquals('string', Property::to_string('string'));
        $this->assertEquals('123', Property::to_string(123));
    }

    public function testToInteger()
    {
        $this->assertEquals(123, Property::to_integer(123));
        $this->assertEquals(123, Property::to_integer('123'));
    }

    public function testToFloat()
    {
        $this->assertEquals(1.23, Property::to_float(1.23));
        $this->assertEquals(123.0, Property::to_float('123'));
    }

    public function testToNumber()
    {
        $this->assertEquals(123, Property::to_number(123));
        $this->assertEquals(123, Property::to_number('123'));
    }

    public function testToBoolean()
    {
        $this->assertTrue(Property::to_boolean(true));
        $this->assertTrue(Property::to_boolean('1'));
        $this->assertFalse(Property::to_boolean(false));
    }

    public function testToDate()
    {
        $this->assertEquals(123, Property::to_date(123));
        $this->assertEquals(123, Property::to_date('123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Property::to_date('Aug-20-2015'));
    }

    public function testToArray()
    {
        $this->assertEquals(['test' => true], Property::to_array('{"test":true}'));
        $this->assertEquals(['test' => true], Property::to_array(['test' => true]));
    }

    public function testToObject()
    {
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Property::to_object('{"test":true}'));
        $this->assertEquals($expected, Property::to_object($expected));
    }
}
