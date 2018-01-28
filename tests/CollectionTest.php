<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Collection;

class CollectionTest extends MockeryTestCase
{
    public function testArrayAccess()
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertEquals(1, $collection[0]);
        $this->assertEquals(2, $collection[1]);
        $this->assertEquals(3, $collection[2]);

        $this->assertTrue(isset($collection[0]));
    }

    public function testArrayAccessNoSet()
    {
        $collection = new Collection([1, 2, 3]);
        $this->expectException(Exception::class);
        $collection[4] = 5;
    }

    public function testArrayAccessNoUnset()
    {
        $collection = new Collection([1, 2, 3]);
        $this->expectException(Exception::class);
        unset($collection[4]);
    }

    public function testIterator()
    {
        $collection = new Collection([1, 2, 3]);

        $total = 0;
        foreach ($collection as $n) {
            $total += $n;
        }
        $this->assertEquals(6, $total);
    }

    public function testCount()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertCount(3, $collection);
    }
}
