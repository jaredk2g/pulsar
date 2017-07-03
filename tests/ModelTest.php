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
use Pulsar\Errors;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\MassAssignmentException;
use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Model;
use Pulsar\ModelEvent;
use Pulsar\Query;
use Pulsar\Relation\BelongsTo;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\HasMany;
use Pulsar\Relation\HasOne;

require_once 'test_models.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        // discard the cached dispatcher to
        // remove any event listeners
        TestModel::getDispatcher(true);
    }

    public function testInjectContainer()
    {
        $c = new \Pimple\Container();
        Model::inject($c);

        $model = new TestModel();
        $this->assertEquals($c, $model->getApp());
    }

    public function testDriverMissing()
    {
        $this->expectException(DriverMissingException::class);
        TestModel::clearDriver();
        TestModel::getDriver();
    }

    public function testDriver()
    {
        $driver = Mockery::mock(DriverInterface::class);
        TestModel::setDriver($driver);

        $this->assertEquals($driver, TestModel::getDriver());

        // setting the driver for a single model sets
        // the driver for all models
        $this->assertEquals($driver, TestModel2::getDriver());
    }

    public function testModelName()
    {
        $this->assertEquals('TestModel', TestModel::modelName());

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('getTablename')
               ->withArgs(['TestModel'])
               ->andReturn('TestModels');
        TestModel::setDriver($driver);
    }

    public function testGetProperties()
    {
        $expected = [
            'id' => [
                'type' => Model::TYPE_INTEGER,
                'mutable' => Model::IMMUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'relation' => [
                'type' => Model::TYPE_NUMBER,
                'relation' => 'TestModel2',
                'null' => true,
                'unique' => false,
                'required' => false,
                'mutable' => Model::MUTABLE,
            ],
            'answer' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'test_hook' => [
                'type' => Model::TYPE_STRING,
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
            ],
            'mutator' => [
                'type' => null,
                'null' => false,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
            ],
            'accessor' => [
                'type' => null,
                'null' => false,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
            ],
        ];

        $this->assertEquals($expected, TestModel::getProperties());
    }

    public function testPropertiesIdOverwrite()
    {
        $expected = [
            'type' => Model::TYPE_STRING,
            'mutable' => Model::MUTABLE,
            'null' => false,
            'unique' => false,
            'required' => false,
        ];

        $this->assertEquals($expected, Person::getProperty('id'));
    }

    public function testGetProperty()
    {
        $expected = [
            'type' => Model::TYPE_INTEGER,
            'mutable' => Model::IMMUTABLE,
            'null' => false,
            'unique' => false,
            'required' => false,
        ];
        $this->assertEquals($expected, TestModel::getProperty('id'));

        $expected = [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'TestModel2',
            'null' => true,
            'unique' => false,
            'required' => false,
            'mutable' => Model::MUTABLE,
        ];
        $this->assertEquals($expected, TestModel::getProperty('relation'));
    }

    public function testPropertiesAutoTimestamps()
    {
        $expected = [
            'id' => [
                'type' => Model::TYPE_NUMBER,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'id2' => [
                'type' => Model::TYPE_NUMBER,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'default' => [
                'type' => null,
                'default' => 'some default value',
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'validate' => [
                'type' => null,
                'validate' => 'email|string:5',
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
            ],
            'validate2' => [
                'type' => null,
                'validate' => 'validate',
                'null' => true,
                'mutable' => Model::MUTABLE,
                'unique' => false,
                'required' => false,
            ],
            'unique' => [
                'type' => null,
                'unique' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'required' => false,
            ],
            'required' => [
                'type' => Model::TYPE_NUMBER,
                'required' => true,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
            ],
            'hidden' => [
                'type' => Model::TYPE_BOOLEAN,
                'default' => false,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'person' => [
                'type' => Model::TYPE_NUMBER,
                'relation' => 'Person',
                'default' => 20,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'array' => [
                'type' => Model::TYPE_ARRAY,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'default' => [
                    'tax' => '%',
                    'discounts' => false,
                    'shipping' => false,
                ],
                'unique' => false,
                'required' => false,
            ],
            'object' => [
                'type' => Model::TYPE_OBJECT,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'mutable_create_only' => [
                'type' => null,
                'mutable' => Model::MUTABLE_CREATE_ONLY,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'protected' => [
                'type' => null,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'created_at' => [
                'type' => Model::TYPE_DATE,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
            ],
            'updated_at' => [
                'type' => Model::TYPE_DATE,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
            ],
        ];

        $model = new TestModel2(); // forces initialize()
        $this->assertEquals($expected, TestModel2::getProperties());
    }

    public function testPropertiesSoftDelete()
    {
        $expected = [
            'id' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'name' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
                'default' => 'Jared',
            ],
            'email' => [
                'type' => Model::TYPE_STRING,
                'mutable' => Model::MUTABLE,
                'null' => false,
                'unique' => false,
                'required' => false,
            ],
            'deleted_at' => [
                'type' => Model::TYPE_DATE,
                'mutable' => Model::MUTABLE,
                'null' => true,
                'unique' => false,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
            ],
        ];

        $model = new Person(); // forces initialize()
        $this->assertEquals($expected, Person::getProperties());
    }

    public function testGetIDProperties()
    {
        $this->assertEquals(['id'], TestModel::getIDProperties());

        $this->assertEquals(['id', 'id2'], TestModel2::getIDProperties());
    }

    public function testGetMutator()
    {
        $this->assertFalse(TestModel::getMutator('id'));
        $this->assertFalse(TestModel2::getMutator('id'));
        $this->assertEquals('setMutatorValue', TestModel::getMutator('mutator'));
    }

    public function testGetAccessor()
    {
        $this->assertFalse(TestModel::getAccessor('id'));
        $this->assertFalse(TestModel2::getAccessor('id'));
        $this->assertEquals('getAccessorValue', TestModel::getAccessor('accessor'));
    }

    public function testCast()
    {
        $property = ['null' => true];
        $this->assertEquals(null, Model::cast($property, ''));

        $property = ['type' => Model::TYPE_STRING, 'null' => false];
        $this->assertEquals('string', Model::cast($property, 'string'));
        $this->assertNull(Model::cast($property, null));

        $property = ['type' => Model::TYPE_BOOLEAN, 'null' => false];
        $this->assertTrue(Model::cast($property, true));
        $this->assertTrue(Model::cast($property, '1'));
        $this->assertFalse(Model::cast($property, false));

        $property = ['type' => Model::TYPE_INTEGER, 'null' => false];
        $this->assertEquals(123, Model::cast($property, 123));
        $this->assertEquals(123, Model::cast($property, '123'));

        $property = ['type' => Model::TYPE_FLOAT, 'null' => false];
        $this->assertEquals(1.23, Model::cast($property, 1.23));
        $this->assertEquals(123.0, Model::cast($property, '123'));

        $property = ['type' => Model::TYPE_NUMBER, 'null' => false];
        $this->assertEquals(123, Model::cast($property, 123));
        $this->assertEquals(123, Model::cast($property, '123'));

        $property = ['type' => Model::TYPE_DATE, 'null' => false];
        $this->assertEquals(123, Model::cast($property, 123));
        $this->assertEquals(123, Model::cast($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), Model::cast($property, 'Aug-20-2015'));

        $property = ['type' => Model::TYPE_ARRAY, 'null' => false];
        $this->assertEquals(['test' => true], Model::cast($property, '{"test":true}'));
        $this->assertEquals(['test' => true], Model::cast($property, ['test' => true]));

        $property = ['type' => Model::TYPE_OBJECT, 'null' => false];
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, Model::cast($property, '{"test":true}'));
        $this->assertEquals($expected, Model::cast($property, $expected));

        $property = ['type' => 'unknown', 'null' => false];
        $this->assertEquals('blah', Model::cast($property, 'blah'));
    }

    public function testGetErrors()
    {
        TestModel::clearErrorStack();
        $model = new TestModel();
        $this->assertInstanceOf(Errors::class, $model->getErrors());

        // set a global stack
        $stack = new Errors();
        TestModel::setErrorStack($stack);
        $stack->add('test');

        $model2 = new TestModel();
        $this->assertEquals($stack, $model2->getErrors());
        $this->assertNotEquals($model->getErrors(), $model2->getErrors());

        $model3 = new TestModel();
        $this->assertEquals($stack, $model3->getErrors());
    }

    public function testGetTablename()
    {
        $model = new TestModel();
        $this->assertEquals('TestModels', $model->getTablename());

        $model = new TestModel(4);
        $this->assertEquals('TestModels', $model->getTablename());

        $model = new Person();
        $this->assertEquals('People', $model->getTablename());
    }

    public function testGetConnection()
    {
        $model = new TestModel();
        $this->assertFalse($model->getConnection());
    }

    public function testId()
    {
        $model = new TestModel(5);

        $this->assertEquals(5, $model->id());

        $model2 = new TestModel($model);
        $this->assertEquals(5, $model2->id());
    }

    public function testMultipleIds()
    {
        $model = new TestModel2([5, 2]);

        $this->assertEquals('5,2', $model->id());

        $model2 = new TestModel(5);
        $model3 = new TestModel2([$model2, 2]);
        $this->assertEquals('5,2', $model3->id());
    }

    public function testIdTypeCast()
    {
        $model = new TestModel('5');
        $this->assertTrue($model->id() === 5, 'id() type casting failed');

        $model = new TestModel(5);
        $this->assertTrue($model->id() === 5, 'id() type casting failed');
    }

    public function testIds()
    {
        $model = new TestModel(3);
        $this->assertEquals(['id' => 3], $model->ids());

        $model = new TestModel2([5, 2]);
        $this->assertEquals(['id' => 5, 'id2' => 2], $model->ids());
    }

    public function testIdsTypeCast()
    {
        $model = new TestModel('3');
        $this->assertTrue($model->ids()['id'] === 3, 'ids() type casting failed');

        $model2 = new TestModel2(['5', '2']);
        $this->assertTrue($model2->ids()['id'] === 5, 'ids() type casting failed');
        $this->assertTrue($model2->ids()['id2'] === 2, 'ids() type casting failed');
    }

    public function testToString()
    {
        $model = new TestModel(1);
        $this->assertEquals('TestModel(1)', (string) $model);
    }

    public function testSetAndGetUnsaved()
    {
        $model = new TestModel(2);

        $model->test = 12345;
        $this->assertEquals(12345, $model->test);

        $model->null = null;
        $this->assertEquals(null, $model->null);

        $model->mutator = 'test';
        $this->assertEquals('TEST', $model->mutator);

        $model->accessor = 'TEST';
        $this->assertEquals('test', $model->accessor);
    }

    public function testIsset()
    {
        $model = new TestModel(1);

        $this->assertFalse(isset($model->test2));

        $model->test = 12345;
        $this->assertTrue(isset($model->test));

        $model->null = null;
        $this->assertTrue(isset($model->null));
    }

    public function testUnset()
    {
        $model = new TestModel(1);

        $model->test = 12345;
        unset($model->test);
        $this->assertFalse(isset($model->test));
    }

    public function testHasNoId()
    {
        $model = new TestModel();
        $this->assertFalse($model->id());
    }

    public function testGetMultipleProperties()
    {
        $model = new TestModel(3);
        $model->relation = '10';
        $model->answer = 42;

        $expected = [
            'id' => 3,
            'relation' => 10,
            'answer' => 42,
        ];

        $values = $model->get(['id', 'relation', 'answer']);
        $this->assertEquals($expected, $values);
    }

    public function testGetFromDb()
    {
        $model = new TestModel(12);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->withArgs([$model])
               ->andReturn(['answer' => 42])
               ->once();

        TestModel::setDriver($driver);

        $this->assertEquals(42, $model->answer);
    }

    public function testGetDefaultValue()
    {
        $model = new TestModel2(12);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel2::setDriver($driver);

        $this->assertEquals('some default value', $model->default);
    }

    public function testToArray()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel(5);

        $expected = [
            'id' => 5,
            'relation' => null,
            'answer' => null,
            'test_hook' => null,
            'appended' => true,
            // this is tacked on in toArrayHook() below
            'toArrayHook' => true,
        ];

        $this->assertEquals($expected, $model->toArray());
    }

    public function testToArrayWithRelationship()
    {
        $model = new RelationshipTestModel(5);
        $expected = [
            'id' => 5,
            'person' => [
                'id' => 10,
                'name' => 'Bob Loblaw',
                'email' => 'bob@example.com',
                'deleted_at' => null,
            ],
        ];
        $this->assertEquals($expected, $model->toArray());
    }

    public function testArrayAccess()
    {
        $model = new TestModel();

        // test offsetExists
        $this->assertFalse(isset($model['test']));
        $model->test = true;
        $this->assertTrue(isset($model['test']));

        // test offsetGet
        $this->assertEquals(true, $model['test']);

        // test offsetSet
        $model['test'] = 'hello world';
        $this->assertEquals('hello world', $model['test']);

        // test offsetUnset
        unset($model['test']);
        $this->assertFalse(isset($model['test']));
    }

    /////////////////////////////
    // CREATE
    /////////////////////////////

    public function testCreate()
    {
        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->withArgs([$newModel, [
                    'mutator' => 'BLAH',
                    'relation' => null,
                    'answer' => 42,
                ]])
               ->andReturn(true)
               ->once();

        $driver->shouldReceive('getCreatedID')
               ->withArgs([$newModel, 'id'])
               ->andReturn(1);

        TestModel::setDriver($driver);

        $newModel->relation = '';
        $newModel->answer = 42;
        $newModel->extra = true;
        $newModel->mutator = 'blah';
        $newModel->array = [];
        $newModel->object = new stdClass();

        $this->assertTrue($newModel->create());
        $this->assertEquals(1, $newModel->id());
        $this->assertEquals(1, $newModel->id);
        $this->assertTrue($newModel->persisted());
    }

    public function testCreateWithSave()
    {
        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->withArgs([$newModel, [
                    'mutator' => 'BLAH',
                    'relation' => null,
                    'answer' => 42,
                ]])
               ->andReturn(true)
               ->once();

        $driver->shouldReceive('getCreatedID')
               ->andReturn(1);

        TestModel::setDriver($driver);

        $newModel->relation = '';
        $newModel->answer = 42;
        $newModel->extra = true;
        $newModel->mutator = 'blah';
        $newModel->array = [];
        $newModel->object = new stdClass();

        $this->assertTrue($newModel->save());
    }

    public function testSaveOrFailCreate()
    {
        $this->expectException(ModelException::class);
        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('createModel')
               ->andReturn(false);
        TestModel::setDriver($driver);

        $newModel->saveOrFail();
    }

    public function testCreateMassAssignment()
    {
        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
            ->withArgs([$newModel, [
                'mutator' => 'BLAH',
                'relation' => null,
                'answer' => 42,
            ]])
            ->andReturn(true)
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$newModel, 'id'])
            ->andReturn(1);

        TestModel::setDriver($driver);

        $params = [
            'relation' => '',
            'answer' => 42,
            'mutator' => 'blah',
        ];

        $this->assertTrue($newModel->create($params));
        $this->assertEquals(1, $newModel->id());
        $this->assertEquals(1, $newModel->id);
    }

    public function testCreateMassAssignmentFail()
    {
        $this->expectException(MassAssignmentException::class);

        $newModel = new TestModel();
        $newModel->create(['not_allowed' => true]);
    }

    public function testCreateMutable()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->andReturn(true)
               ->once();

        TestModel2::setDriver($driver);

        $newModel = new TestModel2();
        $this->assertTrue($newModel->create(['id' => 1, 'id2' => 2, 'required' => 25]));
        $this->assertEquals('1,2', $newModel->id());
    }

    public function testCreateImmutable()
    {
        $newModel = new TestModel2();

        $driver = Mockery::mock(DriverInterface::class);

        $object = new stdClass();
        $object->test = true;

        $driver->shouldReceive('createModel')
               ->andReturnUsing(function ($newModel, $params) use ($object) {
                   unset($params['created_at']);
                   unset($params['updated_at']);

                   $expected = [
                       'id' => 1,
                        'id2' => 2,
                        'required' => 25,
                        'mutable_create_only' => 'test',
                        'default' => 'some default value',
                        'hidden' => false,
                        'array' => [
                            'tax' => '%',
                            'discounts' => false,
                            'shipping' => false,
                        ],
                        'object' => $object,
                        'person' => 20,
                    ];
                   $this->assertEquals($expected, $params);

                   return true;
               })
               ->andReturn(true);

        TestModel2::setDriver($driver);

        $this->assertTrue($newModel->create(['id' => 1, 'id2' => 2, 'required' => 25, 'mutable_create_only' => 'test', 'object' => $object]));
    }

    public function testCreateImmutableId()
    {
        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->andReturn(true);

        $driver->shouldReceive('getCreatedID')
               ->andReturn(1);

        TestModel::setDriver($driver);

        $this->assertTrue($newModel->create(['id' => 100]));
        $this->assertNotEquals(100, $newModel->id());
    }

    public function testCreateAutoTimestamps()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('createModel')
               ->andReturnUsing(function ($model, $params) {
                   $this->assertTrue(isset($params['created_at']));
                   $this->assertTrue(isset($params['updated_at']));
                   $createdAt = strtotime($params['created_at']);
                   $updatedAt = strtotime($params['updated_at']);
                   $this->assertLessThan(3, time() - $createdAt);
                   $this->assertLessThan(3, time() - $updatedAt);

                   return true;
               });
        Model::setDriver($driver);
        $newModel = new TestModel2();
        $newModel->id = 1;
        $newModel->id2 = 2;
        $newModel->required = 25;
        $this->assertTrue($newModel->create());
    }

    public function testCreateWithId()
    {
        $this->expectException(BadMethodCallException::class);

        $model = new TestModel(5);
        $this->assertFalse($model->create(['relation' => '', 'answer' => 42]));
    }

    public function testCreatingListenerFail()
    {
        TestModel::creating(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $newModel = new TestModel();
        $this->assertFalse($newModel->create([]));
    }

    public function testCreatedListenerFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->andReturn(true);

        $driver->shouldReceive('getCreatedID')
               ->andReturn(1);

        TestModel::setDriver($driver);

        TestModel::created(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $newModel = new TestModel();
        $this->assertFalse($newModel->create([]));
    }

    public function testCreateSavingListenerFail()
    {
        TestModel::saving(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $newModel = new TestModel();
        $this->assertFalse($newModel->create());
    }

    public function testCreateSavedListenerFail()
    {
        $driver = Mockery::mock(\Pulsar\Driver\DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->andReturn(true);

        $driver->shouldReceive('getCreatedID')
               ->andReturn(1);

        Model::setDriver($driver);

        TestModel::saved(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $newModel = new TestModel();
        $this->assertFalse($newModel->create());
    }

    public function testCreateNotUnique()
    {
        $errorStack = new Errors();
        TestModel2::setErrorStack($errorStack);

        $query = TestModel2::query();
        TestModel2::setQuery($query);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(1);

        TestModel2::setDriver($driver);

        $model = new TestModel2();

        $create = [
            'id' => 2,
            'id2' => 4,
            'required' => 25,
            'unique' => 'fail',
        ];
        $this->assertFalse($model->create($create));

        // verify error
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['The Unique you chose has already been taken. Please try a different Unique.'], $errorStack->all());

        $this->assertEquals(['unique' => 'fail'], $query->getWhere());
    }

    public function testCreateInvalid()
    {
        $errorStack = new Errors();
        TestModel2::setErrorStack($errorStack);

        $newModel = new TestModel2();
        $this->assertFalse($newModel->create(['id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true]));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate must be a valid email address'], $errorStack->all());

        // repeating the save should clear the error stack
        $this->assertFalse($newModel->create(['id' => 10, 'id2' => 1, 'validate' => 'notanemail', 'required' => true]));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate must be a valid email address'], $errorStack->all());
    }

    public function testCreateMissingRequired()
    {
        $errorStack = new Errors();
        TestModel2::setErrorStack($errorStack);

        $newModel = new TestModel2();
        $this->assertFalse($newModel->create(['id' => 10, 'id2' => 1]));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Required is missing'], $errorStack->all());
    }

    public function testCreateFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
               ->andReturn(false);

        TestModel::setDriver($driver);

        $newModel = new TestModel();
        $this->assertFalse($newModel->create(['relation' => '', 'answer' => 42]));
    }

    /////////////////////////////
    // SET
    /////////////////////////////

    public function testSet()
    {
        $model = new TestModel(10);

        $this->assertTrue($model->set([]));

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->withArgs([$model, ['answer' => 42]])
               ->andReturn(true);

        TestModel::setDriver($driver);

        $this->assertTrue($model->set(['answer' => 42]));
        $this->assertTrue($model->persisted());
    }

    public function testSetWithSave()
    {
        $model = new TestModel(10);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->withArgs([$model, ['answer' => 42]])
               ->andReturn(true);

        TestModel::setDriver($driver);

        $model->answer = 42;
        $this->assertTrue($model->save());
    }

    public function testSaveOrFailUpdate()
    {
        $this->expectException(ModelException::class);
        $model = new TestModel(10);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
               ->andReturn(false);
        TestModel::setDriver($driver);

        $model->answer = 42;
        $model->saveOrFail();
    }

    public function testSetMassAssignment()
    {
        $model = new TestModel2(11);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->andReturnUsing(function ($model, $params) {
                   unset($params['updated_at']);
                   $expected = ['id' => 'hello', 'id2' => 'world'];
                   $this->assertEquals($expected, $params);

                   return true;
               });

        TestModel::setDriver($driver);

        $this->assertTrue($model->set([
            'id' => 'hello',
            'id2' => 'world',
            'nonexistent_property' => 'whatever',
        ]));
    }

    public function testSetMassAssignmentFail()
    {
        $this->expectException(MassAssignmentException::class);

        $newModel = new TestModel(2);
        $newModel->set(['protected' => true]);
    }

    public function testSetImmutableProperties()
    {
        $model = new TestModel2(10);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->andReturnUsing(function ($model, $params) {
                   $this->assertTrue(isset($params['id']));
                   $this->assertFalse(isset($params['mutable_create_only']));

                   return true;
               })
               ->once();

        TestModel::setDriver($driver);

        $this->assertTrue($model->set([
            'id' => 432,
            'mutable_create_only' => 'blah',
        ]));
        $this->assertEquals(10, $model->id);
    }

    public function testSetAutoTimestamps()
    {
        $model = new TestModel2(10);
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
                ->andReturnUsing(function ($model, $params) {
                    $this->assertTrue(isset($params['updated_at']));
                    $updatedAt = strtotime($params['updated_at']);
                    $this->assertLessThan(3, time() - $updatedAt);

                    return true;
                });
        Model::setDriver($driver);
        $model->required = true;
        $this->assertTrue($model->set());
    }

    public function testSetFailWithNoId()
    {
        $this->expectException(BadMethodCallException::class);

        $model = new TestModel();
        $this->assertFalse($model->set(['answer' => 42]));
    }

    public function testUpdatingListenerFail()
    {
        TestModel::updating(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $this->assertFalse($model->set(['answer' => 42]));
    }

    public function testUpdatedListenerFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->andReturn(true);

        TestModel::setDriver($driver);

        TestModel::updated(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $this->assertFalse($model->set(['answer' => 42]));
    }

    public function testUpdateSavingListenerFail()
    {
        TestModel::saving(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $model->answer = 42;
        $this->assertFalse($model->save());
    }

    public function testUpdateSavedListenerFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
               ->andReturn(true);

        Model::setDriver($driver);

        TestModel::saved(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $model->answer = 42;
        $this->assertFalse($model->save());
    }

    public function testSetUnique()
    {
        $query = TestModel2::query();
        TestModel2::setQuery($query);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(0);

        $driver->shouldReceive('loadModel');

        $driver->shouldReceive('updateModel')
               ->andReturn(true);

        TestModel2::setDriver($driver);

        $model = new TestModel2(12);
        $this->assertTrue($model->set(['unique' => 'works']));

        // validate query where statement
        $this->assertEquals(['unique' => 'works'], $query->getWhere());
    }

    public function testSetUniqueSkip()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->andReturn(['unique' => 'works']);

        $driver->shouldReceive('updateModel')
               ->andReturn(true);

        TestModel2::setDriver($driver);

        $model = new TestModel2(12);
        $this->assertTrue($model->set(['unique' => 'works']));
    }

    public function testSetInvalid()
    {
        $errorStack = new Errors();
        TestModel2::setErrorStack($errorStack);

        $model = new TestModel2(15);

        $this->assertFalse($model->set(['validate2' => 'invalid']));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate2 is invalid'], $errorStack->all());

        // repeating the save should reset the error stack
        $this->assertFalse($model->set(['validate2' => 'invalid']));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate2 is invalid'], $errorStack->all());
    }

    public function testSetDeprecated()
    {
        $model = new TestModel(11);
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
               ->andReturn(true);
        Model::setDriver($driver);
        $this->assertTrue($model->set(['answer' => 42]));
        $expected = [
            'answer' => 42,
        ];
        $this->assertEquals($expected, $model::$preSetHookValues);
    }

    /////////////////////////////
    // DELETE
    /////////////////////////////

    public function testDelete()
    {
        $model = new TestModel(1);
        $model->refreshWith(['test' => true]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('deleteModel')
               ->withArgs([$model])
               ->andReturn(true);
        TestModel::setDriver($driver);

        $this->assertTrue($model->delete());
        $this->assertFalse($model->persisted());
        $this->assertEquals(true, $model->test);
        $this->assertTrue($model->isDeleted());
    }

    public function testDeleteWithNoId()
    {
        $this->expectException(BadMethodCallException::class);

        $model = new TestModel();
        $model->refreshWith(['test' => true]);

        $this->assertFalse($model->delete());
        $this->assertTrue($model->persisted());
    }

    public function testDeletingListenerFail()
    {
        TestModel::deleting(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $model->refreshWith(['test' => true]);

        $this->assertFalse($model->delete());
        $this->assertTrue($model->persisted());
        $this->assertFalse($model->isDeleted());
    }

    public function testDeletedListenerFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('deleteModel')
               ->andReturn(true);

        TestModel::setDriver($driver);

        TestModel::deleted(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(100);
        $model->refreshWith(['test' => true]);

        $this->assertFalse($model->delete());
        $this->assertTrue($model->persisted());
        $this->assertFalse($model->isDeleted());
    }

    public function testDeleteFail()
    {
        $model = new TestModel2(1);
        $model->refreshWith(['test' => true]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('deleteModel')
               ->withArgs([$model])
               ->andReturn(false);
        TestModel2::setDriver($driver);

        $this->assertFalse($model->delete());
        $this->assertTrue($model->persisted());
        $this->assertFalse($model->isDeleted());
    }

    public function testSoftDelete()
    {
        $model = new Person(1);
        $model->refreshWith(['test' => true]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
               ->andReturn(true);
        Person::setDriver($driver);

        $this->assertTrue($model->grantAllPermissions()->delete());
        $this->assertTrue($model->persisted());
        $this->assertEquals(true, $model->test);
        $this->assertGreaterThan(0, $model->deleted_at);
        $this->assertTrue($model->isDeleted());
    }

    public function testSoftDeleteRestore()
    {
        $model = new Person(1);
        $model->refreshWith(['test' => true, 'deleted_at' => time()]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
            ->andReturn(true);
        Person::setDriver($driver);

        $this->assertTrue($model->grantAllPermissions()->restore());
        $this->assertTrue($model->persisted());
        $this->assertNull($model->deleted_at);
        $this->assertFalse($model->isDeleted());
    }

    /////////////////////////////
    // Queries
    /////////////////////////////

    public function testQuery()
    {
        $query = TestModel::query();

        $this->assertInstanceOf(Query::class, $query);
        $this->assertInstanceOf(TestModel::class, $query->getModel());
    }

    public function testQueryStatic()
    {
        $query = TestModel::where(['name' => 'Bob']);

        $this->assertInstanceOf(Query::class, $query);
    }

    public function testQuerySoftDelete()
    {
        $query = Person::query();

        $this->assertInstanceOf(Query::class, $query);
        $this->assertInstanceOf(Person::class, $query->getModel());
        $this->assertEquals(['deleted_at IS NOT NULL'], $query->getWhere());
    }

    public function testWithDeleted()
    {
        $query = Person::withDeleted();

        $this->assertInstanceOf(Query::class, $query);
        $this->assertInstanceOf(Person::class, $query->getModel());
        $this->assertEquals([], $query->getWhere());
    }

    public function testFind()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 100, 'answer' => 42]]);

        TestModel::setDriver($driver);

        $model = TestModel::find(100);
        $this->assertInstanceOf('TestModel', $model);
        $this->assertEquals(100, $model->id());
        $this->assertEquals(42, $model->answer);
    }

    public function testFindFail()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $this->assertNull(TestModel::find(101));
    }

    public function testFindMalformedId()
    {
        $this->assertNull(TestModel::find(false));
        $this->assertNull(TestModel2::find(null));
    }

    public function testFindOrFail()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 100, 'answer' => 42]]);

        TestModel::setDriver($driver);

        $model = TestModel::findOrFail(100);
        $this->assertInstanceOf('TestModel', $model);
        $this->assertEquals(100, $model->id());
        $this->assertEquals(42, $model->answer);
    }

    public function testFindOrFailNotFound()
    {
        $this->expectException(ModelNotFoundException::class);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $this->assertFalse(TestModel::findOrFail(101));
    }

    public function testTotalRecords()
    {
        $query = TestModel2::query();
        TestModel2::setQuery($query);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(1);

        TestModel2::setDriver($driver);

        $this->assertEquals(1, TestModel2::totalRecords(['name' => 'John']));

        $this->assertEquals(['name' => 'John'], $query->getWhere());
    }

    public function testTotalRecordsNoCriteria()
    {
        $query = TestModel2::query();
        TestModel2::setQuery($query);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(2);

        TestModel2::setDriver($driver);

        $this->assertEquals(2, TestModel2::totalRecords());

        $this->assertEquals([], $query->getWhere());
    }

    public function testExists()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(1);

        TestModel2::setDriver($driver);

        $model = new TestModel2(12);
        $this->assertTrue($model->exists());
    }

    public function testNotExists()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
               ->andReturn(0);

        TestModel2::setDriver($driver);

        $model = new TestModel2(12);
        $this->assertFalse($model->exists());
    }

    /////////////////////////////
    // Relationships
    /////////////////////////////

    public function testRelation()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturnUsing(function ($query) {
                   $id = $query->getWhere()['id'];

                   return [['id' => $id]];
               });

        TestModel2::setDriver($driver);

        $model = new TestModel2();
        $model->person = 2;

        $person = $model->relation('person');
        $this->assertInstanceOf(Person::class, $person);
        $this->assertEquals(2, $person->id());

        // test if relation model is cached
        $person->name = 'Bob';
        $person2 = $model->relation('person');
        $this->assertEquals('Bob', $person2->name);

        // reset the relation
        $model->person = 3;
        $this->assertEquals(3, $model->relation('person')->id());

        // check other methods for thoroughness...
        unset($model->person);
        $model->person = 4;
        $this->assertEquals(4, $model->relation('person')->id());
    }

    public function testRelationNoId()
    {
        $model = new TestModel();
        $this->assertNull($model->relation('relation'));
    }

    public function testRelationNotFound()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
                ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel();
        $this->assertNull($model->relation('relation'));
    }

    public function testSetRelation()
    {
        $model = new TestModel();
        $relation = new TestModel2(2);
        $model->setRelation('relation', $relation);
        $this->assertEquals($relation, $model->relation('relation'));
        $this->assertEquals(2, $model->relation);
    }

    public function testHasOne()
    {
        $model = new TestModel();

        $relation = $model->hasOne(TestModel2::class);

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('test_model_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsTo()
    {
        $model = new TestModel();
        $model->test_model2_id = 1;

        $relation = $model->belongsTo(TestModel2::class);

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('test_model2_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testHasMany()
    {
        $model = new TestModel();

        $relation = $model->hasMany(TestModel2::class);

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('test_model_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsToMany()
    {
        $model = new TestModel();
        $model->test_model2_id = 1;

        $relation = $model->belongsToMany(TestModel2::class);

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('test_model2_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
        $this->assertEquals('TestModelTestModel2', $relation->getTablename());
    }

    /////////////////////////////
    // Storage
    /////////////////////////////

    public function testRefresh()
    {
        $model = new TestModel2();
        $this->assertEquals($model, $model->refresh());

        $model = new TestModel2(12);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->withArgs([$model])
               ->andReturn([])
               ->once();

        TestModel2::setDriver($driver);

        $this->assertEquals($model, $model->refresh());
    }

    public function testRefreshFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
               ->andReturn(false);

        TestModel2::setDriver($driver);

        $model = new TestModel2(12);
        $this->assertEquals($model, $model->refresh());
    }

    public function testPersisted()
    {
        $model = new TestModel(1);
        $this->assertFalse($model->persisted());
        $model->refreshWith(['id' => 1, 'test' => true]);
        $this->assertTrue($model->persisted());
    }
}
