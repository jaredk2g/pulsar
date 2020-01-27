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

use Garage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Person;
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Pulsar\Relation\HasMany;

class HasManyTest extends MockeryTestCase
{
    public static $driver;

    public static function setUpBeforeClass()
    {
        self::$driver = Mockery::mock(DriverInterface::class);
        Model::setDriver(self::$driver);
    }

    public function testInitQuery()
    {
        $person = new Person(10);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        $this->assertEquals(['person_id' => 10], $relation->getQuery()->getWhere());
    }

    public function testGetResults()
    {
        $person = new Person(10);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        self::$driver->shouldReceive('queryModels')
            ->andReturn([['id' => 11], ['id' => 12]]);

        $result = $relation->getResults();

        $this->assertCount(2, $result);

        foreach ($result as $m) {
            $this->assertInstanceOf(Garage::class, $m);
        }

        $this->assertEquals(11, $result[0]->id());
        $this->assertEquals(12, $result[1]->id());
    }

    public function testEmpty()
    {
        $person = new Person();
        $person->person_id = null;

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        $this->assertNull($relation->getResults());
    }

    public function testSave()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        $garage = new Garage(2);
        $garage->refreshWith(['location' => '']);
        $garage->location = 'My House';

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$garage, ['location' => 'My House', 'person_id' => 100]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($garage, $relation->save($garage));

        $this->assertTrue($garage->persisted());
    }

    public function testCreate()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        self::$driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertEquals(['location' => 'My House', 'person_id' => 100], $params);

                return true;
            });

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $garage = $relation->create(['location' => 'My House']);

        $this->assertInstanceOf(Garage::class, $garage);
        $this->assertTrue($garage->persisted());
    }

    public function testAttach()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        $garage = new Garage(5);
        $garage->refreshWith(['person_id' => null]);

        self::$driver->shouldReceive('updateModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertInstanceOf(Garage::class, $model);
                $this->assertEquals(['person_id' => 100], $params);

                return true;
            })
            ->once();

        $this->assertEquals($relation, $relation->attach($garage));

        $this->assertTrue($garage->persisted());
    }

    public function testDetach()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        $garage = new Garage(2);
        $garage->refreshWith(['person_id' => 100]);

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$garage, ['person_id' => null]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($relation, $relation->detach($garage));
    }

    public function testSync()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Garage', 'person_id');

        self::$driver = Mockery::mock(DriverInterface::class);

        self::$driver->shouldReceive('count')
            ->andReturn(3);

        self::$driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                $this->assertInstanceOf(Garage::class, $query->getModel());
                $this->assertEquals(['person_id NOT IN (1,2,3)'], $query->getWhere());

                return [['id' => 3], ['id' => 4], ['id' => 5]];
            });

        self::$driver->shouldReceive('deleteModel')
            ->andReturn(true)
            ->times(3);

        Model::setDriver(self::$driver);

        $this->assertEquals($relation, $relation->sync([1, 2, 3]));
    }
}
