<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Pulsar\Relation\HasMany;

class HasManyTest extends PHPUnit_Framework_TestCase
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

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        $this->assertEquals(['person_id' => 10], $relation->getQuery()->getWhere());
    }

    public function testGetResults()
    {
        $person = new Person(10);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        self::$driver->shouldReceive('queryModels')
            ->andReturn([['id' => 11], ['id' => 12]]);

        $result = $relation->getResults();

        $this->assertCount(2, $result);

        foreach ($result as $m) {
            $this->assertInstanceOf(Car::class, $m);
        }

        $this->assertEquals(11, $result[0]->id());
        $this->assertEquals(12, $result[1]->id());
    }

    public function testEmpty()
    {
        $person = new Person();
        $person->person_id = null;

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        $this->assertNull($relation->getResults());
    }

    public function testSave()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        $car = new Car(2);
        $car->refreshWith(['type' => '']);
        $car->type = 'Aston Martin';

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$car, ['type' => 'Aston Martin', 'person_id' => 100]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($car, $relation->save($car));

        $this->assertTrue($car->persisted());
    }

    public function testCreate()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        self::$driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertEquals(['type' => 'Aston Martin', 'person_id' => 100], $params);

                return true;
            });

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $car = $relation->create(['type' => 'Aston Martin']);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertTrue($car->persisted());
    }

    public function testAttach()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        $car = new Car(5);
        $car->refreshWith(['person_id' => null]);

        self::$driver->shouldReceive('updateModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertInstanceOf(Car::class, $model);
                $this->assertEquals(['person_id' => 100], $params);

                return true;
            })
            ->once();

        $this->assertEquals($relation, $relation->attach($car));

        $this->assertTrue($car->persisted());
    }

    public function testDetach()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        $car = new Car(2);
        $car->refreshWith(['person_id' => 100]);

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$car, ['person_id' => null]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($relation, $relation->detach($car));
    }

    public function testSync()
    {
        $person = new Person(100);

        $relation = new HasMany($person, 'id', 'Car', 'person_id');

        self::$driver = Mockery::mock(DriverInterface::class);

        self::$driver->shouldReceive('totalRecords')
            ->andReturn(3);

        self::$driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                $this->assertInstanceOf(Car::class, $query->getModel());
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
