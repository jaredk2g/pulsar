<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests\Relation;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Pulsar\Relation\HasOne;
use Pulsar\Tests\Models\Balance;
use Pulsar\Tests\Models\Person;

class HasOneTest extends MockeryTestCase
{
    public static $driver;

    public static function setUpBeforeClass(): void
    {
        self::$driver = Mockery::mock(DriverInterface::class);
        Model::setDriver(self::$driver);
    }

    public function testInitQuery()
    {
        $person = new Person(['id' => 10]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        $query = $relation->getQuery();
        $this->assertInstanceOf(Balance::class, $query->getModel());
        $this->assertEquals(['person_id' => 10], $query->getWhere());
        $this->assertEquals(1, $query->getLimit());
    }

    public function testGetResults()
    {
        $person = new Person(['id' => 10]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        self::$driver->shouldReceive('queryModels')
            ->andReturn([['id' => 11]]);

        $result = $relation->getResults();
        $this->assertInstanceOf(Balance::class, $result);
        $this->assertEquals(11, $result->id());
    }

    public function testEmpty()
    {
        $person = new Person(['id' => null]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        $this->assertNull($relation->getResults());
    }

    public function testSave()
    {
        $person = new Person(['id' => 100]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        $balance = new Balance(['id' => 20]);
        $balance->refreshWith(['amount' => 200]);

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$balance, ['person_id' => 100]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($balance, $relation->save($balance));

        $this->assertTrue($balance->persisted());
    }

    public function testCreate()
    {
        $person = new Person(['id' => 100]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        self::$driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertInstanceOf(Balance::class, $model);
                $this->assertEquals(['amount' => 5000, 'person_id' => 100], $params);

                return true;
            })
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $balance = $relation->create(['amount' => 5000]);

        $this->assertInstanceOf(Balance::class, $balance);
        $this->assertTrue($balance->persisted());
    }

    public function testAttach()
    {
        $person = new Person(['id' => 100]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        $balance = new Balance();

        self::$driver->shouldReceive('createModel')
            ->withArgs([$balance, ['person_id' => 100]])
            ->andReturn(true)
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $this->assertEquals($relation, $relation->attach($balance));

        $this->assertTrue($balance->persisted());
    }

    public function testDetach()
    {
        $person = new Person(['id' => 100]);

        $relation = new HasOne($person, 'id', Balance::class, 'person_id');

        self::$driver->shouldReceive('updateModel')
            ->andReturn(true)
            ->once();

        $this->assertEquals($relation, $relation->detach());
    }
}
