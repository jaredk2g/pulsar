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
use Pulsar\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $query = new Query(TestModel::class);
        $this->assertEquals('TestModel', $query->getModel());
    }

    public function testLimit()
    {
        $query = new Query();

        $this->assertEquals(100, $query->getLimit());
        $this->assertEquals($query, $query->limit(500));
        $this->assertEquals(500, $query->getLimit());
    }

    public function testStart()
    {
        $query = new Query();

        $this->assertEquals(0, $query->getStart());
        $this->assertEquals($query, $query->start(10));
        $this->assertEquals(10, $query->getStart());
    }

    public function testSort()
    {
        $query = new Query();

        $this->assertEquals([], $query->getSort());
        $this->assertEquals($query, $query->sort('name asc, id DESC,invalid,wrong direction'));
        $this->assertEquals([['name', 'asc'], ['id', 'desc']], $query->getSort());
    }

    public function testWhere()
    {
        $query = new Query();

        $this->assertEquals([], $query->getWhere());
        $this->assertEquals($query, $query->where(['test' => true]));
        $this->assertEquals(['test' => true], $query->getWhere());

        $query->where('test', false);
        $this->assertEquals(['test' => false], $query->getWhere());

        $query->where('some condition');
        $this->assertEquals(['test' => false, 'some condition'], $query->getWhere());

        $query->where('balance', 100, '>=');
        $this->assertEquals(['test' => false, 'some condition', ['balance', 100, '>=']], $query->getWhere());
    }

    public function testJoin()
    {
        $query = new Query();

        $this->assertEquals([], $query->getJoins());

        $this->assertEquals($query, $query->join('Person', 'author', 'id'));
        $this->assertEquals([['Person', 'author', 'id']], $query->getJoins());
    }

    public function testWith()
    {
        $query = new Query();

        $this->assertEquals([], $query->getWith());

        $this->assertEquals($query, $query->with('author'));
        $this->assertEquals(['author'], $query->getWith());
    }

    public function testExecute()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
            [
                'id' => 102,
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf(Person::class, $model);
        }

        $this->assertEquals(100, $result[0]->id());
        $this->assertEquals(102, $result[1]->id());

        $this->assertEquals('Sherlock', $result[0]->name);
        $this->assertEquals('John', $result[1]->name);
    }

    public function testExecuteMultipleIds()
    {
        $query = new Query(TestModel2::class);

        $driver = Mockery::mock(DriverInterface::class);

        $data = [
            [
                'id' => 100,
                'id2' => 101,
            ],
            [
                'id' => 102,
                'id2' => 103,
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf(TestModel2::class, $model);
        }

        $this->assertEquals('100,101', $result[0]->id());
        $this->assertEquals('102,103', $result[1]->id());
    }

    public function testExecuteEagerLoading()
    {
        $query = new Query(TestModel2::class);
        $query->with('person');

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
               ->andReturnUsing(function ($query) {
                   if ($query->getModel() instanceof Person && $query->getWhere() == ['id IN (1,2)']) {
                       return [
                           [
                               'id' => 2,
                           ],
                           [
                               'id' => 1,
                           ],
                       ];
                   } elseif ($query->getModel() == TestModel2::class) {
                       return [
                           [
                               'id' => 100,
                               'id2' => 101,
                               'person' => 1,
                           ],
                           [
                               'id' => 102,
                               'id2' => 103,
                               'person' => 2,
                           ],
                           [
                               'id' => 102,
                               'id2' => 103,
                               'person' => null,
                           ],
                       ];
                   }
               });

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(3, $result);

        $person1 = $result[0]->relation('person');
        $this->assertInstanceOf(Person::class, $person1);
        $this->assertEquals(1, $person1->id());
        $person2 = $result[1]->relation('person');
        $this->assertInstanceOf(Person::class, $person2);
        $this->assertEquals(2, $person2->id());
        $this->assertNull($result[2]->relation('person'));
    }

    public function testExecuteEagerLoadingNoRelations()
    {
        $query = new Query(TestModel2::class);
        $query->with('person');

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn([
                    [
                        'id' => 100,
                        'id2' => 101,
                        'person' => null,
                    ],
                    [
                        'id' => 102,
                        'id2' => 103,
                        'person' => null,
                    ],
                    [
                        'id' => 102,
                        'id2' => 103,
                        'person' => null,
                    ],
                ]);

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(3, $result);

        $this->assertNull($result[0]->relation('person'));
        $this->assertNull($result[1]->relation('person'));
        $this->assertNull($result[2]->relation('person'));
    }

    public function testAll()
    {
        $query = new Query(TestModel::class);

        $all = $query->all();
        $this->assertInstanceOf(Iterator::class, $all);
    }

    public function testFirst()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->first();

        $this->assertInstanceOf(Person::class, $result);
        $this->assertEquals(100, $result->id());
        $this->assertEquals('Sherlock', $result->name);
    }

    public function testFirstLimit()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
            [
                'id' => 102,
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
               ->withArgs([$query])
               ->andReturn($data);

        Person::setDriver($driver);

        $result = $query->first(2);

        $this->assertEquals(2, $query->getLimit());

        $this->assertCount(2, $result);
        foreach ($result as $model) {
            $this->assertInstanceOf(Person::class, $model);
        }

        $this->assertEquals(100, $result[0]->id());
        $this->assertEquals(102, $result[1]->id());

        $this->assertEquals('Sherlock', $result[0]->name);
        $this->assertEquals('John', $result[1]->name);
    }
}
