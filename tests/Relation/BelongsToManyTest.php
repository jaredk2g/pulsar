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

use Group;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Person;
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\Pivot;

class BelongsToManyTest extends MockeryTestCase
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

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        $this->assertEquals('group_person', $relation->getTablename());

        $query = $relation->getQuery();
        $this->assertInstanceOf(Group::class, $query->getModel());
        $joins = $query->getJoins();
        $this->assertCount(1, $joins);
        $this->assertInstanceOf(Pivot::class, $joins[0][0]);
        $this->assertEquals('group_person', $joins[0][0]->getTablename());
        $this->assertEquals('group_id', $joins[0][1]);
        $this->assertEquals('id', $joins[0][2]);
        $this->assertEquals(['person_id' => 10], $query->getWhere());
    }

    public function testGetResults()
    {
        $person = new Person(10);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        self::$driver->shouldReceive('queryModels')
            ->andReturn([['id' => 11], ['id' => 12]]);

        $result = $relation->getResults();

        $this->assertCount(2, $result);

        foreach ($result as $m) {
            $this->assertInstanceOf(Group::class, $m);
        }

        $this->assertEquals(11, $result[0]->id());
        $this->assertEquals(12, $result[1]->id());
    }

    public function testEmpty()
    {
        $person = new Person();

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        $this->assertNull($relation->getResults());
    }

    public function testSave()
    {
        $person = new Person(2);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        $group = new Group(5);
        $group->name = 'Test';

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$group, ['name' => 'Test']])
            ->andReturn(true)
            ->once();

        self::$driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertInstanceOf(Pivot::class, $model);
                $this->assertEquals(['person_id' => 2, 'group_id' => 5], $params);

                return true;
            })
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $this->assertEquals($group, $relation->save($group));

        $this->assertTrue($group->persisted());

        // verify pivot
        $pivot = $group->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertEquals('group_person', $pivot->getTablename());
        $this->assertTrue($pivot->persisted());
    }

    public function testCreate()
    {
        $person = new Person(2);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        self::$driver->shouldReceive('createModel')
            ->andReturn(true);

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $group = $relation->create(['name' => 'Test']);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertTrue($group->persisted());

        // verify pivot
        $pivot = $group->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertEquals('group_person', $pivot->getTablename());
        $this->assertTrue($pivot->persisted());
    }

    public function testAttach()
    {
        $person = new Person(2);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        $group = new Group(3);

        self::$driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $params) {
                $this->assertInstanceOf(Pivot::class, $model);
                $this->assertEquals(['person_id' => 2, 'group_id' => 3], $params);

                return true;
            })
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $this->assertEquals($relation, $relation->attach($group));

        $pivot = $group->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertEquals('group_person', $pivot->getTablename());
        $this->assertTrue($pivot->persisted());
    }

    public function testDetach()
    {
        $person = new Person(2);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        $group = new Group();
        $group->person_id = 2;
        $group->pivot = Mockery::mock();
        $group->pivot->shouldReceive('delete')->once();

        $this->assertEquals($relation, $relation->detach($group));
    }

    public function testSync()
    {
        $person = new Person(2);

        $relation = new BelongsToMany($person, 'person_id', 'group_person', Group::class, 'group_id');

        self::$driver = Mockery::mock(DriverInterface::class);

        self::$driver->shouldReceive('count')
            ->andReturn(3);

        self::$driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                $this->assertInstanceOf(Pivot::class, $query->getModel());
                $this->assertEquals('group_person', $query->getModel()->getTablename());
                $this->assertEquals(['group_id NOT IN (1,2,3)', 'person_id' => 2], $query->getWhere());

                return [['id' => 3], ['id' => 4], ['id' => 5]];
            });

        self::$driver->shouldReceive('deleteModel')
            ->andReturn(true)
            ->times(3);

        Model::setDriver(self::$driver);

        $this->assertEquals($relation, $relation->sync([1, 2, 3]));
    }
}
