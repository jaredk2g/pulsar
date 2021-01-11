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

use Iterator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Driver\DriverInterface;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Query;
use Pulsar\Tests\Models\Category;
use Pulsar\Tests\Models\Garage;
use Pulsar\Tests\Models\Person;
use Pulsar\Tests\Models\Post;
use Pulsar\Tests\Models\TestModel;
use Pulsar\Tests\Models\TestModel2;

class QueryTest extends MockeryTestCase
{
    public function testGetModel()
    {
        $query = new Query(TestModel::class);
        $this->assertEquals(TestModel::class, $query->getModel());
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

        $query->with('author');
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

    public function testExecuteEagerLoadingNoResults()
    {
        $query = new Query(TestModel2::class);
        $query->with('person');

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                return [];
            });

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(0, $result);
    }

    public function testExecuteEagerLoadingBelongsTo()
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
                } elseif (TestModel2::class == $query->getModel()) {
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
                        [
                            'id' => 103,
                            'id2' => 104,
                            'person' => 2,
                        ],
                        [
                            'id' => 105,
                            'id2' => 106,
                            'person' => 1,
                        ],
                    ];
                }
            });

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(5, $result);

        $person1 = $result[0]->relation('person');
        $this->assertInstanceOf(Person::class, $person1);
        $this->assertEquals(1, $person1->id());
        $person2 = $result[1]->relation('person');
        $this->assertInstanceOf(Person::class, $person2);
        $this->assertEquals(2, $person2->id());
        $this->assertNull($result[2]->relation('person'));
        $person4 = $result[3]->relation('person');
        $this->assertInstanceOf(Person::class, $person4);
        $this->assertEquals(2, $person4->id());
        $person5 = $result[4]->relation('person');
        $this->assertInstanceOf(Person::class, $person5);
        $this->assertEquals(1, $person5->id());
    }

    public function testExecuteEagerLoadingHasOne()
    {
        $query = new Query(Person::class);
        $query->with('garage');

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                if ($query->getModel() instanceof Garage && $query->getWhere() == ['person_id IN (1,2,3,4,5)']) {
                    return [
                        [
                            'id' => 100,
                            'person_id' => 1,
                            'make' => 'Nissan',
                            'model' => 'GTR',
                        ],
                        [
                            'id' => 101,
                            'person_id' => 2,
                            'make' => 'Aston Martin',
                            'model' => 'DB11',
                        ],
                        [
                            'id' => 103,
                            'person_id' => 4,
                            'make' => 'Lamborghini',
                            'model' => 'Aventador',
                        ],
                        [
                            'id' => 104,
                            'person_id' => 5,
                            'make' => 'Tesla',
                            'model' => 'Roadster',
                        ],
                    ];
                } elseif (Person::class == $query->getModel()) {
                    return [
                        [
                            'id' => 1,
                        ],
                        [
                            'id' => 2,
                        ],
                        [
                            'id' => 3,
                        ],
                        [
                            'id' => 4,
                        ],
                        [
                            'id' => 5,
                        ],
                    ];
                }
            });

        TestModel2::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(5, $result);

        $garage1 = $result[0]->relation('garage');
        $this->assertInstanceOf(Garage::class, $garage1);
        $this->assertEquals(100, $garage1->id());
        $garage2 = $result[1]->relation('garage');
        $this->assertInstanceOf(Garage::class, $garage2);
        $this->assertEquals(101, $garage2->id());
        $this->assertNull($result[2]->relation('garage'));
        $garage4 = $result[3]->relation('garage');
        $this->assertInstanceOf(Garage::class, $garage4);
        $this->assertEquals(103, $garage4->id());
        $garage5 = $result[4]->relation('garage');
        $this->assertInstanceOf(Garage::class, $garage5);
        $this->assertEquals(104, $garage5->id());
    }

    public function testExecuteEagerLoadingHasMany()
    {
        $query = new Query(Category::class);
        $query->with('posts');

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
            ->andReturnUsing(function ($query) {
                if ($query->getModel() instanceof Post && $query->getWhere() == ['category_id IN (1,2,3)']) {
                    return [
                        [
                            'id' => 100,
                            'category_id' => 1,
                        ],
                        [
                            'id' => 101,
                            'category_id' => 2,
                        ],
                        [
                            'id' => 102,
                            'category_id' => 2,
                        ],
                        [
                            'id' => 103,
                            'category_id' => 2,
                        ],
                    ];
                } elseif (Category::class == $query->getModel()) {
                    return [
                        [
                            'id' => 1,
                        ],
                        [
                            'id' => 2,
                        ],
                        [
                            'id' => 3,
                        ],
                    ];
                }
            });

        Category::setDriver($driver);

        $result = $query->execute();

        $this->assertCount(3, $result);

        $posts1 = $result[0]->relation('posts');
        $this->assertCount(1, $posts1);
        foreach ($posts1 as $post) {
            $this->assertInstanceOf(Post::class, $post);
        }
        $this->assertEquals(100, $posts1[0]->id());

        $posts2 = $result[1]->relation('posts');
        $this->assertCount(3, $posts2);
        foreach ($posts2 as $post) {
            $this->assertInstanceOf(Post::class, $post);
        }
        $this->assertEquals(101, $posts2[0]->id());
        $this->assertEquals(102, $posts2[1]->id());
        $this->assertEquals(103, $posts2[2]->id());

        $posts3 = $result[2]->relation('posts');
        $this->assertCount(0, $posts3);
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

    public function testOne()
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

        $result = $query->one();

        $this->assertInstanceOf(Person::class, $result);
        $this->assertEquals(100, $result->id());
        $this->assertEquals('Sherlock', $result->name);
    }

    public function testOneZeroResults()
    {
        $this->expectException(ModelNotFoundException::class);

        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
            ->withArgs([$query])
            ->andReturn([]);

        Person::setDriver($driver);

        $query->one();
    }

    public function testOneTooManyResults()
    {
        $this->expectException(ModelNotFoundException::class);

        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);

        $data = [
            [
                'id' => 100,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
            [
                'id' => 101,
                'name' => 'Sherlock',
                'email' => 'sherlock@example.com',
            ],
        ];

        $driver->shouldReceive('queryModels')
            ->withArgs([$query])
            ->andReturn($data);

        Person::setDriver($driver);

        $query->one();
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

    public function testCount()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('count')
            ->withArgs([$query])
            ->andReturn(10);

        Person::setDriver($driver);

        $this->assertEquals(10, $query->count());
    }

    public function testSum()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('sum')
            ->withArgs([$query, 'balance'])
            ->andReturn(50.2);

        Person::setDriver($driver);

        $this->assertEquals(50.2, $query->sum('balance'));
    }

    public function testAverage()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('average')
            ->withArgs([$query, 'balance'])
            ->andReturn(1);

        Person::setDriver($driver);

        $this->assertEquals(1, $query->average('balance'));
    }

    public function testMax()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('max')
            ->withArgs([$query, 'balance'])
            ->andReturn(2.5);

        Person::setDriver($driver);

        $this->assertEquals(2.5, $query->max('balance'));
    }

    public function testMin()
    {
        $query = new Query(Person::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('min')
            ->withArgs([$query, 'balance'])
            ->andReturn(0);

        Person::setDriver($driver);

        $this->assertEquals(0, $query->min('balance'));
    }

    public function testSet()
    {
        $model = Mockery::mock();
        $model->shouldReceive('set')
            ->withArgs([['test' => true]])
            ->once();
        $model2 = Mockery::mock();
        $model2->shouldReceive('set')
            ->withArgs([['test' => true]])
            ->once();
        $query = Mockery::mock('Pulsar\Query[all]', [new TestModel()]);
        $query->shouldReceive('all')
            ->andReturn([$model, $model2]);
        $this->assertEquals(2, $query->set(['test' => true]));
    }

    public function testDelete()
    {
        $model = Mockery::mock();
        $model->shouldReceive('delete')->once();
        $model2 = Mockery::mock();
        $model2->shouldReceive('delete')->once();
        $query = Mockery::mock('Pulsar\Query[all]', [new TestModel()]);
        $query->shouldReceive('all')
            ->andReturn([$model, $model2]);
        $this->assertEquals(2, $query->delete());
    }
}
