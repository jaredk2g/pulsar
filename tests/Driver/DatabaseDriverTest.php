<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use JAQB\QueryBuilder;
use Pimple\Container;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Exception\DriverException;
use Pulsar\Model;
use Pulsar\Query;

class DatabaseDriverTest extends PHPUnit_Framework_TestCase
{
    public static $app;

    public static function setUpBeforeClass()
    {
        self::$app = new Container();
    }

    public function testTablename()
    {
        $driver = new DatabaseDriver(self::$app);

        $this->assertEquals('TestModels', $driver->getTablename('TestModel'));

        $model = new TestModel(4);
        $this->assertEquals('TestModels', $driver->getTablename($model));
    }

    public function testSerializeValue()
    {
        $driver = new DatabaseDriver(self::$app);

        $this->assertEquals('string', $driver->serializeValue('string'));

        $arr = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($arr));

        $obj = new stdClass();
        $obj->test = true;
        $this->assertEquals('{"test":true}', $driver->serializeValue($obj));
    }

    public function testUnserializeValue()
    {
        $driver = new DatabaseDriver(self::$app);

        $property = ['null' => true];
        $this->assertEquals(null, $driver->unserializeValue($property, ''));

        $property = ['type' => Model::TYPE_STRING, 'null' => false];
        $this->assertEquals('string', $driver->unserializeValue($property, 'string'));

        $property = ['type' => Model::TYPE_BOOLEAN, 'null' => false];
        $this->assertTrue($driver->unserializeValue($property, true));
        $this->assertTrue($driver->unserializeValue($property, '1'));
        $this->assertFalse($driver->unserializeValue($property, false));

        $property = ['type' => Model::TYPE_NUMBER, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));

        $property = ['type' => Model::TYPE_DATE, 'null' => false];
        $this->assertEquals(123, $driver->unserializeValue($property, 123));
        $this->assertEquals(123, $driver->unserializeValue($property, '123'));
        $this->assertEquals(mktime(0, 0, 0, 8, 20, 2015), $driver->unserializeValue($property, 'Aug-20-2015'));

        $property = ['type' => Model::TYPE_ARRAY, 'null' => false];
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, '{"test":true}'));
        $this->assertEquals(['test' => true], $driver->unserializeValue($property, ['test' => true]));

        $property = ['type' => Model::TYPE_OBJECT, 'null' => false];
        $expected = new stdClass();
        $expected->test = true;
        $this->assertEquals($expected, $driver->unserializeValue($property, '{"test":true}'));
        $this->assertEquals($expected, $driver->unserializeValue($property, $expected));
    }

    public function testCreateModel()
    {
        $db = Mockery::mock(QueryBuilder::class);

        // insert query mock
        $stmt = Mockery::mock(PDOStatement::class);
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')
                ->andReturn($stmt);
        $into = Mockery::mock();
        $into->shouldReceive('into')
             ->withArgs(['People'])
             ->andReturn($execute);
        $db->shouldReceive('insert')
           ->withArgs([['answer' => 42, 'array' => '{"test":true}']])
           ->andReturn($into)
           ->once();

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);

        $model = new Person();
        $this->assertTrue($driver->createModel($model, ['answer' => 42, 'array' => ['test' => true]]));
    }

    public function testCreateModelFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver when creating the Person: error');
        $db = Mockery::mock();
        $db->shouldReceive('insert')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);
        $model = new Person();
        $driver->createModel($model, []);
    }

    public function testGetCreatedID()
    {
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('getPDO->lastInsertId')
            ->andReturn('1');

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);

        $model = new Person();
        $this->assertEquals(1, $driver->getCreatedID($model, 'id'));
    }

    public function testGetCreatedIDFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver when getting the ID of the new Person: error');
        $db = Mockery::mock();
        $db->shouldReceive('getPDO->lastInsertId')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        $model = new Person();
        $driver->getCreatedID($model, 'id');
    }

    public function testLoadModel()
    {
        // select query mock
        $one = Mockery::mock();
        $one->shouldReceive('one')
            ->andReturn(['name' => 'John']);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([['id' => 12]])
              ->andReturn($one);
        $from = Mockery::mock();
        $from->shouldReceive('from')
             ->withArgs(['People'])
             ->andReturn($where);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
           ->andReturn($from)
           ->once();

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);

        $model = new Person(12);
        $this->assertEquals(['name' => 'John'], $driver->loadModel($model));
    }

    public function testLoadModelFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver when loading an instance of Person: error');
        $db = Mockery::mock();
        $db->shouldReceive('select->from->where->one')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        $model = new Person(12);
        $driver->loadModel($model);
    }

    public function testUpdateModel()
    {
        // update query mock
        $stmt = Mockery::mock(PDOStatement::class);
        $execute = Mockery::mock();
        $execute->shouldReceive('execute')->andReturn($stmt);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([['id' => 11]])
              ->andReturn($execute);
        $values = Mockery::mock();
        $values->shouldReceive('values')
               ->withArgs([['name' => 'John', 'array' => '{"test":true}']])
               ->andReturn($where);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('update')
           ->withArgs(['People'])
           ->andReturn($values);

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);

        $model = new Person(11);

        $this->assertTrue($driver->updateModel($model, []));

        $parameters = ['name' => 'John', 'array' => ['test' => true]];
        $this->assertTrue($driver->updateModel($model, $parameters));
    }

    public function testUpdateModelFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver when updating the Person: error');
        // update query mock
        $db = Mockery::mock();
        $db->shouldReceive('update')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);
        $model = new Person(11);
        $driver->updateModel($model, ['name' => 'John']);
    }

    public function testDeleteModel()
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('delete->where->execute')
           ->andReturn($stmt);

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);

        $model = new Person(10);
        $this->assertTrue($driver->deleteModel($model));
    }

    public function testDeleteModelFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver while deleting the Person: error');
        $stmt = Mockery::mock(PDOStatement::class);
        $db = Mockery::mock();
        $db->shouldReceive('delete->where->execute')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);
        $model = new Person(10);
        $driver->deleteModel($model);
    }

    public function testTotalRecords()
    {
        $query = new Query('Person');

        // select query mock
        $scalar = Mockery::mock();
        $scalar->shouldReceive('scalar')
               ->andReturn(1);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([[]])
              ->andReturn($scalar);
        $from = Mockery::mock();
        $from->shouldReceive('from')
             ->withArgs(['People'])
             ->andReturn($where);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
           ->withArgs(['count(*)'])
           ->andReturn($from);

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->totalRecords($query));
    }

    public function testTotalRecordsFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver while getting the number of Person objects');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock();
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);
        $driver->totalRecords($query);
    }

    public function testQueryModels()
    {
        $query = new Query('Person');
        $query->where('id', 50, '>')
              ->where(['city' => 'Austin'])
              ->where('RAW SQL')
              ->where('People.alreadyDotted', true)
              ->join('Group', 'group', 'id')
              ->sort('name asc')
              ->limit(5)
              ->start(10);

        // select query mock
        $all = Mockery::mock();
        $all->shouldReceive('all')
            ->andReturn([['test' => true]]);
        $all->shouldReceive('join')
             ->withArgs(['Groups', 'People.group=Groups.id'])
             ->once();
        $orderBy = Mockery::mock();
        $orderBy->shouldReceive('orderBy')
                ->withArgs([[['People.name', 'asc']]])
                ->andReturn($all);
        $limit = Mockery::mock();
        $limit->shouldReceive('limit')
             ->withArgs([5, 10])
             ->andReturn($orderBy);
        $where = Mockery::mock();
        $where->shouldReceive('where')
              ->withArgs([[['People.id', 50, '>'], 'People.city' => 'Austin', 'RAW SQL', 'People.alreadyDotted' => true]])
              ->andReturn($limit);
        $from = Mockery::mock();
        $from->shouldReceive('from')
             ->withArgs(['People'])
             ->andReturn($where);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
           ->withArgs(['People.*'])
           ->andReturn($from);

        self::$app['db'] = $db;

        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);

        $this->assertEquals([['test' => true]], $driver->queryModels($query));
    }

    public function testQueryModelsFail()
    {
        $this->setExpectedException(DriverException::class, 'An error occurred in the database driver while performing the Person query: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock('JAQB\Query\SelectQuery[all]');
        $db->shouldReceive('all')
            ->andThrow(new PDOException('error'));
        self::$app['db'] = $db;
        $driver = new DatabaseDriver(self::$app);
        Person::setDriver($driver);
        $driver->queryModels($query);
    }
}
