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

use BadMethodCallException;
use Defuse\Crypto\Key;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Driver\DriverInterface;
use Pulsar\Errors;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\MassAssignmentException;
use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Interfaces\TranslatorInterface;
use Pulsar\Model;
use Pulsar\ModelEvent;
use Pulsar\Property;
use Pulsar\Query;
use Pulsar\Relation\Relationship;
use Pulsar\Tests\Models\Customer;
use Pulsar\Tests\Models\Garage;
use Pulsar\Tests\Models\Invoice;
use Pulsar\Tests\Models\Person;
use Pulsar\Tests\Models\RelationshipTestModel;
use Pulsar\Tests\Models\TestModel;
use Pulsar\Tests\Models\TestModel2;
use Pulsar\Tests\Models\TransactionModel;
use Pulsar\Translator;
use Pulsar\Type;
use stdClass;

class ModelTest extends MockeryTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Errors::setTranslator(new Translator());
    }

    protected function tearDown()
    {
        // discard the cached dispatcher to
        // remove any event listeners
        TestModel::getDispatcher(true);
        Person::getDispatcher(true);
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
                'type' => Type::INTEGER,
                'mutable' => Property::IMMUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'relation' => [
                'type' => Type::INTEGER,
                'relation' => TestModel2::class,
                'relation_type' => Relationship::BELONGS_TO,
                'foreign_key' => 'id',
                'local_key' => 'relation',
                'null' => true,
                'required' => false,
                'mutable' => Property::MUTABLE,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'pivot_tablename' => null,
            ],
            'answer' => [
                'type' => Type::STRING,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'test_hook' => [
                'type' => Type::STRING,
                'null' => true,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'mutator' => [
                'type' => null,
                'null' => false,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'accessor' => [
                'type' => null,
                'null' => false,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'encrypted' => [
                'type' => null,
                'null' => false,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'validate' => 'encrypt',
                'default' => null,
                'persisted' => true,
                'encrypted' => true,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
        ];

        $model = new TestModel(); // ensure initialize() is called
        $properties = TestModel::definition();
        $result = array_map(function ($value) { return $value->toArray(); }, $properties->all());
        $this->assertEquals($expected, $result);
    }

    public function testPropertiesIdOverwrite()
    {
        $expected = [
            'type' => Type::STRING,
            'mutable' => Property::MUTABLE,
            'null' => false,
            'required' => false,
            'validate' => null,
            'default' => null,
            'persisted' => true,
            'encrypted' => false,
            'relation' => null,
            'relation_type' => null,
            'foreign_key' => null,
            'local_key' => null,
            'pivot_tablename' => null,
        ];

        $this->assertEquals($expected, Person::definition()->get('id')->toArray());
    }

    public function testGetProperty()
    {
        $expected = [
            'type' => Type::INTEGER,
            'mutable' => Property::IMMUTABLE,
            'null' => false,
            'required' => false,
            'validate' => null,
            'default' => null,
            'persisted' => true,
            'encrypted' => false,
            'relation' => null,
            'relation_type' => null,
            'foreign_key' => null,
            'local_key' => null,
            'pivot_tablename' => null,
        ];
        $this->assertEquals($expected, TestModel::definition()->get('id')->toArray());

        $expected = [
            'type' => Type::INTEGER,
            'relation' => TestModel2::class,
            'relation_type' => Relationship::BELONGS_TO,
            'foreign_key' => 'id',
            'local_key' => 'relation',
            'null' => true,
            'required' => false,
            'mutable' => Property::MUTABLE,
            'validate' => null,
            'default' => null,
            'persisted' => true,
            'encrypted' => false,
            'pivot_tablename' => null,
        ];
        $this->assertEquals($expected, TestModel::definition()->get('relation')->toArray());
    }

    public function testPropertiesAutoTimestamps()
    {
        $expected = [
            'id' => [
                'type' => Type::INTEGER,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'id2' => [
                'type' => Type::INTEGER,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'default' => [
                'type' => null,
                'default' => 'some default value',
                'persisted' => true,
                'encrypted' => false,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'validate' => [
                'type' => null,
                'validate' => ['email', ['string', 'min' => 5]],
                'null' => true,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'validate2' => [
                'type' => null,
                'validate' => ['callable', 'fn' => 'modelValidate'],
                'null' => true,
                'mutable' => Property::MUTABLE,
                'required' => false,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'unique' => [
                'type' => null,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => ['unique', 'column' => 'unique'],
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'required' => [
                'type' => Type::INTEGER,
                'required' => true,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'hidden' => [
                'type' => Type::BOOLEAN,
                'default' => false,
                'persisted' => true,
                'encrypted' => false,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'person' => [
                'type' => Type::INTEGER,
                'relation' => Person::class,
                'relation_type' => Relationship::BELONGS_TO,
                'foreign_key' => 'id',
                'local_key' => 'person',
                'default' => 20,
                'persisted' => true,
                'encrypted' => false,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'pivot_tablename' => null,
            ],
            'array' => [
                'type' => Type::ARRAY,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'default' => [
                    'tax' => '%',
                    'discounts' => false,
                    'shipping' => false,
                ],
                'persisted' => true,
                'encrypted' => false,
                'required' => false,
                'validate' => null,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'object' => [
                'type' => Type::OBJECT,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'mutable_create_only' => [
                'type' => null,
                'mutable' => Property::MUTABLE_CREATE_ONLY,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'protected' => [
                'type' => null,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'created_at' => [
                'type' => Type::DATE,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'updated_at' => [
                'type' => Type::DATE,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
        ];
        $model = new TestModel2(); // forces initialize()
        $result = array_map(function ($value) { return $value->toArray(); }, TestModel2::definition()->all());
        $this->assertEquals($expected, $result);
    }

    public function testPropertiesSoftDelete()
    {
        $expected = [
            'id' => [
                'type' => Type::STRING,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'name' => [
                'type' => Type::STRING,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => 'Jared',
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'email' => [
                'type' => Type::STRING,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'validate' => 'email',
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'deleted_at' => [
                'type' => Type::DATE,
                'mutable' => Property::MUTABLE,
                'null' => true,
                'required' => false,
                'validate' => 'timestamp|db_timestamp',
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'garage' => [
                'type' => null,
                'mutable' => Property::MUTABLE,
                'null' => false,
                'required' => false,
                'relation' => Garage::class,
                'relation_type' => 'has_one',
                'foreign_key' => 'person_id',
                'local_key' => 'id',
                'validate' => null,
                'default' => null,
                'persisted' => false,
                'encrypted' => false,
                'pivot_tablename' => null,
            ],
        ];

        $model = new Person(); // forces initialize()
        $result = array_map(function ($value) { return $value->toArray(); }, Person::definition()->all());
        $this->assertEquals($expected, $result);
    }

    public function testGetIDProperties()
    {
        $this->assertEquals(['id'], TestModel::getIDProperties());

        $this->assertEquals(['id', 'id2'], TestModel2::getIDProperties());
    }

    public function testGetMutator()
    {
        $this->assertNull(TestModel::getMutator('id'));
        $this->assertNull(TestModel2::getMutator('id'));
        $this->assertEquals('setMutatorValue', TestModel::getMutator('mutator'));
    }

    public function testGetAccessor()
    {
        $this->assertNull(TestModel::getAccessor('id'));
        $this->assertNull(TestModel2::getAccessor('id'));
        $this->assertEquals('getAccessorValue', TestModel::getAccessor('accessor'));
    }

    public function testGetErrors()
    {
        $model = new TestModel();
        $this->assertInstanceOf(Errors::class, $model->getErrors());
    }

    public function testGetTablename()
    {
        $model = new TestModel();
        $this->assertEquals('TestModels', $model->getTablename());

        $model = new TestModel(['id' => 4]);
        $this->assertEquals('TestModels', $model->getTablename());

        $model = new Person();
        $this->assertEquals('People', $model->getTablename());
    }

    public function testGetConnection()
    {
        $model = new TestModel();
        $this->assertNull($model->getConnection());
    }

    public function testId()
    {
        $model = new TestModel(['id' => 5]);
        $this->assertEquals(5, $model->id());
    }

    public function testMultipleIds()
    {
        $model = new TestModel2(['id' => 5, 'id2' => 2]);
        $this->assertEquals('5,2', $model->id());
    }

    public function testIdTypeCast()
    {
        $model = new TestModel(['id' => '5']);
        $this->assertTrue(5 === $model->id(), 'id() type casting failed');

        $model = new TestModel(['id' => 5]);
        $this->assertTrue(5 === $model->id(), 'id() type casting failed');
    }

    public function testIds()
    {
        $model = new TestModel(['id' => 3]);
        $this->assertEquals(['id' => 3], $model->ids());

        $model = new TestModel2(['id' => 5, 'id2' => 2]);
        $this->assertEquals(['id' => 5, 'id2' => 2], $model->ids());
    }

    public function testIdsTypeCast()
    {
        $model = new TestModel(['id' => '3']);
        $this->assertTrue(3 === $model->ids()['id'], 'ids() type casting failed');

        $model2 = new TestModel2(['id' => '5', 'id2' => '2']);
        $this->assertTrue(5 === $model2->ids()['id'], 'ids() type casting failed');
        $this->assertTrue(2 === $model2->ids()['id2'], 'ids() type casting failed');
    }

    public function testToString()
    {
        $model = new TestModel(['id' => 1]);
        $model->answer = 42;
        $expected = 'Pulsar\Tests\Models\TestModel({
    "answer": 42,
    "id": 1
})';
        $this->assertEquals($expected, (string) $model);
    }

    public function testSetAndGetUnsaved()
    {
        $model = new TestModel(['id' => 2]);

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
        $model = new TestModel(['id' => 1]);

        $this->assertFalse(isset($model->test2));
        $this->assertTrue(isset($model->answer));

        $model->test = 12345;
        $this->assertTrue(isset($model->test));

        $model->null = null;
        $this->assertTrue(isset($model->null));

        $model = new TestModel();
        $this->assertFalse(isset($model->test));
        $this->assertFalse(isset($model->not_a_property));
        $this->assertTrue(isset($model->answer));

        $model->test = 'hello world';
        $this->assertTrue(isset($model->test));

        $model->not_a_property = 'hello world';
        $this->assertTrue(isset($model->not_a_property));

        $model = new TestModel(['id' => 1, 'test' => 'hello world']);
        $this->assertFalse(isset($model->test));
        $model->test = 'hello world';
        $this->assertTrue(isset($model->test));
        $model->test = 'goodbye world';
        $this->assertTrue(isset($model->test));
    }

    public function testUnset()
    {
        $model = new TestModel(['id' => 1]);

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
        $model = new TestModel(['id' => 3]);
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
        $model = new TestModel(['id' => 12]);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('loadModel')
            ->withArgs([$model])
            ->andReturn(['answer' => 42])
            ->once();

        TestModel::setDriver($driver);

        $this->assertEquals(42, $model->answer);
    }

    public function testGetNonExistentPropertyDoesNotRefresh()
    {
        $model = new TestModel(['id' => 12]);

        $this->assertNull($model->non_existent_property);
    }

    public function testGetDefaultValue()
    {
        $model = new TestModel2(['id' => 12]);

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

        $model = new TestModel(['id' => 5]);

        $expected = [
            'id' => 5,
            'relation' => null,
            'answer' => null,
            'test_hook' => null,
            'appended' => true,
            'encrypted' => null,
        ];

        $this->assertEquals($expected, $model->toArray());
    }

    public function testToArrayWithModel()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('queryModels')
            ->andReturn([]);

        RelationshipTestModel::setDriver($driver);

        $model = new RelationshipTestModel(['id' => 5]);
        $expected = [
            'id' => 5,
            'person' => [
                'id' => 10,
                'name' => 'Bob Loblaw',
                'email' => 'bob@example.com',
                'deleted_at' => null,
                // the `garage` relationship should not be included by default
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

    public function testDirtyNoHasChangedCheck()
    {
        $model = new TestModel();
        $this->assertFalse($model->dirty('test'));
        $this->assertFalse($model->dirty('not_a_property'));

        $model->test = 'hello world';
        $this->assertTrue($model->dirty('test'));

        $model->test = null;
        $this->assertTrue($model->dirty('test'));

        $model->not_a_property = 'hello world';
        $this->assertTrue($model->dirty('not_a_property'));

        $model = new TestModel(['id' => 1, 'test' => 'hello world']);
        $this->assertFalse($model->dirty('test'));
        $model->test = 'hello world';
        $this->assertTrue($model->dirty('test'));
        $model->test = 'goodbye world';
        $this->assertTrue($model->dirty('test'));
    }

    public function testDirtyHasChangedCheck()
    {
        $model = new TestModel();
        $this->assertFalse($model->dirty('test', true));
        $this->assertFalse($model->dirty('not_a_property', true));

        $model->test = 'hello world';
        $this->assertTrue($model->dirty('test', true));

        $model->test = null;
        $this->assertFalse($model->dirty('test', true));

        $model->not_a_property = 'hello world';
        $this->assertTrue($model->dirty('not_a_property', true));

        $model = new TestModel(['id' => 1, 'test' => 'hello world']);
        $this->assertFalse($model->dirty('test', true));
        $model->test = 'hello world';
        $this->assertFalse($model->dirty('test', true));
        $model->test = 'goodbye world';
        $this->assertTrue($model->dirty('test', true));
        $model->test = null;
        $this->assertTrue($model->dirty('test', true));
    }

    /////////////////////////////
    // CREATE
    /////////////////////////////

    public function testCreate()
    {
        $newModel = new TestModel();

        $key = Key::loadFromAsciiSafeString('def000001ef79749946a5b1c38efd9fbc6b632c3feac272b57cf0d433ae129afd9e997dbaf74e62b66c846ca41b965a68799932eb197e1ef0499039b0d37462fc4eb9063');
        Type::setEncryptionKey($key);

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
        $this->expectExceptionMessage('Failed to save TestModel');

        $newModel = new TestModel();

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('createModel')
            ->andReturn(false);
        TestModel::setDriver($driver);

        $newModel->saveOrFail();
    }

    public function testSaveOrFailCreateValidationError()
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Failed to save TestModel2: ');

        $newModel = new TestModel2();
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

        $model = new TestModel(['id' => 5]);
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
        $query = TestModel2::query();
        TestModel2::setQuery($query);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('count')
            ->andReturn(1);

        TestModel2::setDriver($driver);

        $model = new TestModel2();
        $errorStack = $model->getErrors();

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
        $newModel = new TestModel2();
        $errorStack = $newModel->getErrors();
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
        $newModel = new TestModel2();
        $errorStack = $newModel->getErrors();
        $this->assertFalse($newModel->create(['id' => 10, 'id2' => 1]));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Required is missing'], $errorStack->all());
    }

    public function testCreateTransactions()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('createModel')
            ->andReturn(true);
        $driver->shouldReceive('getCreatedID')
            ->andReturn(1);
        $driver->shouldReceive('commitTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $newModel = new TransactionModel();
        $this->assertTrue($newModel->create(['name' => 'db transactions rock']));
    }

    public function testCreateTransactionsFail()
    {
        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('rollBackTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $newModel = new TransactionModel();
        $newModel->create([]);
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

    public function testCreateEncrypted()
    {
        $newModel = new TestModel();

        $key = Key::loadFromAsciiSafeString('def000001ef79749946a5b1c38efd9fbc6b632c3feac272b57cf0d433ae129afd9e997dbaf74e62b66c846ca41b965a68799932eb197e1ef0499039b0d37462fc4eb9063');
        Type::setEncryptionKey($key);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
            ->andReturnUsing(function ($model, $values) {
                $this->assertTrue(isset($values['encrypted']));
                $this->assertNotEquals('encrypted value', $values['encrypted']);

                return true;
            })
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$newModel, 'id'])
            ->andReturn(1);

        TestModel::setDriver($driver);

        $newModel->encrypted = 'encrypted value';

        $this->assertTrue($newModel->create());
        $this->assertEquals('encrypted value', $newModel->encrypted);
    }

    /////////////////////////////
    // SET
    /////////////////////////////

    public function testSet()
    {
        $model = new TestModel(['id' => 10]);

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
        $model = new TestModel(['id' => 10]);

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
        $this->expectExceptionMessage('Failed to save TestModel');
        $model = new TestModel(['id' => 10]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
            ->andReturn(false);
        TestModel::setDriver($driver);

        $model->answer = 42;
        $model->saveOrFail();
    }

    public function testSaveOrFailUpdateValidationError()
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Failed to save TestModel2: ');
        $model = new TestModel2(['id' => 10]);

        $model->validate = 'not an email';
        $model->saveOrFail();
    }

    public function testSetMassAssignment()
    {
        $model = new TestModel2(['id' => 10, 'id2' => 11]);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
            ->andReturnUsing(function ($model, $params) {
                unset($params['updated_at']);
                $expected = ['id' => 12, 'id2' => 13];
                $this->assertEquals($expected, $params);

                return true;
            });

        TestModel::setDriver($driver);

        $this->assertTrue($model->set([
            'id' => 12,
            'id2' => 13,
            'nonexistent_property' => 'whatever',
        ]));
    }

    public function testSetMassAssignmentFail()
    {
        $this->expectException(MassAssignmentException::class);

        $newModel = new TestModel(['id' => 2]);
        $newModel->set(['protected' => true]);
    }

    public function testSetImmutableProperties()
    {
        $model = new TestModel2(['id' => 10, 'id2' => 11, 'mutable_create_only' => 'test']);

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
        $this->assertEquals('test', $model->mutable_create_only);
    }

    public function testSetAutoTimestamps()
    {
        $model = new TestModel2(['id' => 10, 'id2' => 11]);
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

        $model = new TestModel(['id' => 100]);
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

        $model = new TestModel(['id' => 100]);
        $this->assertFalse($model->set(['answer' => 42]));
    }

    public function testUpdateSavingListenerFail()
    {
        TestModel::saving(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $model = new TestModel(['id' => 100]);
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

        $model = new TestModel(['id' => 100]);
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

        $model = new TestModel2(['id' => 12, 'id2' => 13]);
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

        $model = new TestModel2(['id' => 12, 'id2' => 13]);
        $this->assertTrue($model->set(['unique' => 'works']));
    }

    public function testSetInvalid()
    {
        $model = new TestModel2(['id' => 15, 'id2' => 16]);
        $errorStack = $model->getErrors();

        $this->assertFalse($model->set(['validate2' => 'invalid']));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate2 is invalid'], $errorStack->all());

        // repeating the save should reset the error stack
        $this->assertFalse($model->set(['validate2' => 'invalid']));
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Validate2 is invalid'], $errorStack->all());
    }

    public function testSetTransactions()
    {
        $model = new TransactionModel(['id' => 10]);

        $this->assertTrue($model->set([]));

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('updateModel')
            ->andReturn(true);
        $driver->shouldReceive('commitTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $this->assertTrue($model->set(['name' => 'db transactions rock']));
    }

    public function testSetTransactionsFail()
    {
        $model = new TransactionModel(['id' => 10]);

        $this->assertTrue($model->set([]));

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('rollBackTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $model->set(['name' => 'fail']);
    }

    public function testSetEncrypted()
    {
        $model = new TestModel(['id' => 10]);

        $this->assertTrue($model->set([]));

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
            ->andReturnUsing(function ($model, $values) {
                $this->assertTrue(isset($values['encrypted']));
                $this->assertNotEquals('encrypted value', $values['encrypted']);

                return true;
            })
            ->once();

        TestModel::setDriver($driver);

        $model->encrypted = 'encrypted value';
        $this->assertTrue($model->set());
        $this->assertTrue($model->persisted());
        $this->assertEquals('encrypted value', $model->encrypted);
    }

    public function testSetEncryptedNotModified()
    {
        $model = new TestModel(['id' => 10, 'encrypted' => 'encrypted value']);

        $this->assertTrue($model->set([]));

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
            ->andReturn(true);

        TestModel::setDriver($driver);

        $model->answer = 42;
        $this->assertTrue($model->set());
        $this->assertTrue($model->persisted());
        $this->assertEquals('encrypted value', $model->encrypted);
    }

    /////////////////////////////
    // DELETE
    /////////////////////////////

    public function testDelete()
    {
        $model = new TestModel(['id' => 1]);
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

        $model = new TestModel(['id' => 100]);
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

        $model = new TestModel(['id' => 100]);
        $model->refreshWith(['test' => true]);

        $this->assertFalse($model->delete());
        $this->assertTrue($model->persisted());
        $this->assertFalse($model->isDeleted());
    }

    public function testDeleteFail()
    {
        $model = new TestModel2(['id' => 1, 'id2' => 2]);
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

    public function testDeleteTransactions()
    {
        $model = new TransactionModel(['id' => 1]);
        $model->refreshWith(['test' => true]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('deleteModel')
            ->andReturn(true);
        $driver->shouldReceive('commitTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $this->assertTrue($model->delete());
    }

    public function testDeleteTransactionsFail()
    {
        $model = new TransactionModel(['id' => 1]);
        $model->refreshWith(['name' => 'delete fail']);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('startTransaction')
            ->once();
        $driver->shouldReceive('rollBackTransaction')
            ->once();

        TransactionModel::setDriver($driver);

        $model->delete();
    }

    public function testSoftDelete()
    {
        $model = new Person(['id' => 1]);
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
        $model = new Person(['id' => 1]);
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

    public function testRestoreUnsupportedModel()
    {
        $this->expectException(BadMethodCallException::class);

        $model = new TestModel(['id' => 1]);

        $model->restore();
    }

    public function testRestoreNotDeleted()
    {
        $this->expectException(BadMethodCallException::class);

        $model = new Person(['id' => 1]);
        $model->refreshWith(['test' => true, 'deleted_at' => null]);

        $model->restore();
    }

    public function testRestoreUpdatingEventFail()
    {
        $model = new Person(['id' => 1]);
        $model->refreshWith(['test' => true, 'deleted_at' => time()]);

        Person::updating(function (ModelEvent $event) {
            $event->stopPropagation();
        });
        $this->assertFalse($model->restore());
    }

    public function testRestoreUpdatedEventFail()
    {
        $model = new Person(['id' => 1]);
        $model->refreshWith(['test' => true, 'deleted_at' => time()]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('updateModel')
            ->andReturn(true);
        Person::setDriver($driver);

        Person::updated(function (ModelEvent $event) {
            $event->stopPropagation();
        });

        $this->assertFalse($model->restore());
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
        $this->assertInstanceOf(TestModel::class, $model);
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
        $this->assertInstanceOf(TestModel::class, $model);
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
        $relation = new TestModel2(['id' => 2, 'id2' => 3]);
        $model->setRelation('relation', $relation);
        $this->assertEquals($relation, $model->relation('relation'));
        $this->assertEquals('2,3', $model->relation);
    }

    /////////////////////////////
    // Belongs To Relationship
    /////////////////////////////

    public function testGetPropertiesBelongsTo()
    {
        $expected = [
            'id' => [
                'type' => 'integer',
                'mutable' => 'immutable',
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
            'customer' => [
                'type' => null,
                'mutable' => 'mutable',
                'null' => false,
                'required' => true,
                'validate' => null,
                'default' => null,
                'persisted' => false,
                'encrypted' => false,
                'relation' => Customer::class,
                'relation_type' => 'belongs_to',
                'foreign_key' => 'id',
                'local_key' => 'customer_id',
                'pivot_tablename' => null,
            ],
            'customer_id' => [
                'type' => 'integer',
                'mutable' => 'mutable',
                'null' => false,
                'required' => false,
                'validate' => null,
                'default' => null,
                'persisted' => true,
                'encrypted' => false,
                'relation' => null,
                'relation_type' => null,
                'foreign_key' => null,
                'local_key' => null,
                'pivot_tablename' => null,
            ],
        ];

        $result = array_map(function ($value) { return $value->toArray(); }, Invoice::definition()->all());
        $this->assertEquals($expected, $result);
    }

    public function testSetPropertyBelongsTo()
    {
        $customer = new Customer(['id' => 123]);
        $customer->name = 'Test';
        $invoice = new Invoice();
        $invoice->customer = $customer;
        $this->assertEquals($customer, $invoice->customer);
        $this->assertEquals('Test', $invoice->customer->name);
        $this->assertEquals(123, $invoice->customer_id);

        // setting to null should be supported
        $invoice->customer = null;
        $this->assertNull($invoice->customer);
        $this->assertNull($invoice->customer_id);
    }

    public function testInvalidSetBelongsTo()
    {
        $this->expectException(ModelException::class);
        $invoice = new Invoice();
        $invoice->customer = 1234;
    }

    public function testCreateBelongsTo()
    {
        $invoice = new Invoice();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
            ->withArgs([$invoice, [
                'customer_id' => 123,
            ]])
            ->andReturn(true)
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$invoice, 'id'])
            ->andReturn(1);

        TestModel::setDriver($driver);

        $customer = new Customer(['id' => 123]);
        $invoice->customer = $customer;

        $this->assertTrue($invoice->create());
        $this->assertEquals(1, $invoice->id());
        $this->assertEquals(1, $invoice->id);
        $this->assertTrue($invoice->persisted());
        $this->assertEquals($customer, $invoice->customer);
        $this->assertEquals(123, $invoice->customer_id);
    }

    public function testCreateBelongsToMissingRequired()
    {
        $invoice = new Invoice();
        $errorStack = $invoice->getErrors();
        $this->assertFalse($invoice->save());
        $this->assertCount(1, $errorStack->all());
        $this->assertEquals(['Customer is missing'], $errorStack->all());
    }

    public function testCreateBelongsToWithNewRelationshipModel()
    {
        $customer = new Customer(['name' => 'Test']);
        $invoice = new Invoice();

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
            ->withArgs([$customer, [
                'name' => 'Test',
            ]])
            ->andReturn(true)
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$customer, 'id'])
            ->andReturn(123);

        $driver->shouldReceive('createModel')
            ->withArgs([$invoice, [
                'customer_id' => 123,
            ]])
            ->andReturn(true)
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$invoice, 'id'])
            ->andReturn(1);

        TestModel::setDriver($driver);

        $invoice->customer = $customer;
        $this->assertTrue($invoice->create());
        $this->assertEquals(1, $invoice->id());
        $this->assertEquals(1, $invoice->id);
        $this->assertTrue($invoice->persisted());
        $this->assertEquals($customer, $invoice->customer);
        $this->assertEquals(123, $invoice->customer_id);
    }

    public function testSetBelongsTo()
    {
        $invoice = new Invoice(['id' => 10]);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('updateModel')
            ->withArgs([$invoice, ['customer_id' => 123]])
            ->andReturn(true);

        TestModel::setDriver($driver);

        $customer = new Customer(['id' => 123]);
        $invoice->customer = $customer;
        $this->assertTrue($invoice->save());
        $this->assertTrue($invoice->persisted());
        $this->assertEquals($customer, $invoice->customer);
        $this->assertEquals(123, $invoice->customer_id);
    }

    public function testSetBelongsToWithNewRelationshipModel()
    {
        $customer = new Customer(['name' => 'Test']);
        $invoice = new Invoice(['id' => 10]);

        $driver = Mockery::mock(DriverInterface::class);

        $driver->shouldReceive('createModel')
            ->withArgs([$customer, [
                'name' => 'Test',
            ]])
            ->andReturn(true)
            ->once();

        $driver->shouldReceive('getCreatedID')
            ->withArgs([$customer, 'id'])
            ->andReturn(123);

        $driver->shouldReceive('updateModel')
            ->withArgs([$invoice, ['customer_id' => 123]])
            ->andReturn(true);

        TestModel::setDriver($driver);

        $invoice->customer = $customer;
        $this->assertTrue($invoice->save());
        $this->assertTrue($invoice->persisted());
        $this->assertEquals($customer, $invoice->customer);
        $this->assertEquals(123, $invoice->customer_id);
    }

    public function testToArrayBelongsTo()
    {
        $invoice = new Invoice(['id' => 10]);
        $customer = new Customer(['id' => 123, 'name' => 'Test']);
        $invoice->customer = $customer;
        $expected = [
            'id' => 10,
            'customer_id' => 123,
            // the `customer` relationship should not be included by default
        ];
        $this->assertEquals($expected, $invoice->toArray());
    }

    public function testGetFromDbBelongsTo()
    {
        $invoice = new Invoice(['id' => 10, 'customer_id' => 100]);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
            ->andReturn([['id' => 100, 'name' => 'Bob Loblaw']]);

        Invoice::setDriver($driver);

        $this->assertInstanceOf(Customer::class, $invoice->customer);
        $this->assertEquals(100, $invoice->customer->id);
        $this->assertEquals('Bob Loblaw', $invoice->customer->name);
    }

    /////////////////////////////
    // Storage
    /////////////////////////////

    public function testRefresh()
    {
        $model = new TestModel2();
        $this->assertEquals($model, $model->refresh());

        $model = new TestModel2(['id' => 12, 'id2' => 13]);

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
            ->andReturn(null);

        TestModel2::setDriver($driver);

        $model = new TestModel2(['id' => 12]);
        $this->assertEquals($model, $model->refresh());
    }

    public function testPersisted()
    {
        $model = new TestModel();
        $this->assertFalse($model->persisted());
        $model = new TestModel(['id' => 1]);
        $this->assertFalse($model->persisted());
        $model->refreshWith(['id' => 1, 'test' => true]);
        $this->assertTrue($model->persisted());
    }

    /////////////////////////////
    // Validations
    /////////////////////////////

    public function testValid()
    {
        $model = new TestModel();
        $model->relation = '';
        $model->answer = 42;
        $model->mutator = 'blah';

        $this->assertTrue($model->valid());
    }

    public function testValidFail()
    {
        $model = new TestModel2();
        $model->id = 10;
        $model->id2 = 1;
        $model->validate = 'notanemail';
        $model->required = true;

        $this->assertFalse($model->valid());
        $this->assertEquals(['Validate must be a valid email address'], $model->getErrors()->all());

        // repeat validations should clear error stack
        $this->assertFalse($model->valid());
        $this->assertEquals(['Validate must be a valid email address'], $model->getErrors()->all());
    }

    public function testValidFailPropertyTitle()
    {
        $model = new Person();
        $model->email = 'notanemail';

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')
            ->withArgs(['pulsar.properties.Person.email'])
            ->andReturn('Title');
        $translator->shouldReceive('translate')
            ->withArgs(['pulsar.validation.email', ['field' => 'email', 'field_name' => 'Title'], false, '{{field_name}} must be a valid email address'])
            ->andReturn('Title must be a valid email address');
        $errors = $model->getErrors();
        $errors->setTranslator($translator);

        $this->assertFalse($model->valid());
        $this->assertEquals(['Title must be a valid email address'], $model->getErrors()->all());
    }
}
