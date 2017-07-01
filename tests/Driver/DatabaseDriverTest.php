<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use JAQB\ConnectionManager;
use JAQB\QueryBuilder;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Exception\DriverException;
use Pulsar\Query;

class DatabaseDriverTest extends PHPUnit_Framework_TestCase
{
    public function testGetConnectionFromManager()
    {
        $qb = new QueryBuilder();
        $manager = new ConnectionManager();
        $manager->add('test', $qb);
        $driver = new DatabaseDriver();
        $this->assertEquals($driver, $driver->setConnectionManager($manager));
        $this->assertEquals($manager, $driver->getConnectionManager());

        $this->assertEquals($qb, $driver->getConnection(false));
    }

    public function testGetConnectionById()
    {
        $qb = new QueryBuilder();
        $manager = new ConnectionManager();
        $manager->add('test', $qb);
        $driver = new DatabaseDriver();
        $this->assertEquals($driver, $driver->setConnectionManager($manager));
        $this->assertEquals($manager, $driver->getConnectionManager());

        $this->assertEquals($qb, $driver->getConnection('test'));
    }

    public function testGetConnectionFromManagerMissing()
    {
        $this->expectException(DriverException::class);

        $manager = new ConnectionManager();
        $driver = new DatabaseDriver();
        $driver->setConnectionManager($manager);

        $driver->getConnection(false);
    }

    public function testGetConnectionFromManagerDoesNotExist()
    {
        $this->expectException(DriverException::class);

        $manager = new ConnectionManager();
        $driver = new DatabaseDriver();
        $driver->setConnectionManager($manager);

        $driver->getConnection('test');
    }

    public function testSetConnection()
    {
        $db = Mockery::mock(QueryBuilder::class);
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        $this->assertEquals($db, $driver->getConnection(false));
    }

    public function testGetConnectionMissing()
    {
        $this->expectException(DriverException::class);
        $driver = new DatabaseDriver();
        $driver->getConnection(false);
    }

    public function testSerializeValue()
    {
        $driver = new DatabaseDriver();

        $this->assertEquals('string', $driver->serializeValue('string'));

        $arr = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($arr));

        $obj = new stdClass();
        $obj->test = true;
        $this->assertEquals('{"test":true}', $driver->serializeValue($obj));
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

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $model = new Person();
        $this->assertTrue($driver->createModel($model, ['answer' => 42, 'array' => ['test' => true]]));
    }

    public function testCreateModelFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver when creating the Person: error');
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('insert')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $model = new Person();
        $driver->createModel($model, []);
    }

    public function testGetCreatedID()
    {
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('lastInsertId')
            ->andReturn('1');

        $driver = new DatabaseDriver();
        $driver->setConnection($db);

        $model = new Person();
        $this->assertEquals(1, $driver->getCreatedID($model, 'id'));
    }

    public function testGetCreatedIDFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver when getting the ID of the new Person: error');
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('lastInsertId')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
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

        $driver = new DatabaseDriver();
        $driver->setConnection($db);

        $model = new Person(12);
        $this->assertEquals(['name' => 'John'], $driver->loadModel($model));
    }

    public function testLoadModelNotFound()
    {
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select->from->where->one')
            ->andReturn(false);
        $driver = new DatabaseDriver();
        $driver->setConnection($db);

        $model = new Person(12);
        $this->assertFalse($driver->loadModel($model));
    }

    public function testLoadModelFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver when loading an instance of Person: error');
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select->from->where->one')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
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

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $model = new Person(11);

        $this->assertTrue($driver->updateModel($model, []));

        $parameters = ['name' => 'John', 'array' => ['test' => true]];
        $this->assertTrue($driver->updateModel($model, $parameters));
    }

    public function testUpdateModelFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver when updating the Person: error');
        // update query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('update')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
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

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $model = new Person(10);
        $this->assertTrue($driver->deleteModel($model));
    }

    public function testDeleteModelFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while deleting the Person: error');
        $stmt = Mockery::mock(PDOStatement::class);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('delete->where->execute')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $model = new Person(10);
        $driver->deleteModel($model);
    }

    public function testCount()
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
        $agg = Mockery::mock();
        $agg->shouldReceive('count')
            ->andReturn($from);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andReturn($agg);

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->count($query));
    }

    public function testCountFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while getting the number of Person objects');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->count($query);
    }

    public function testSum()
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
        $agg = Mockery::mock();
        $agg->shouldReceive('sum')
            ->withArgs(['balance'])
            ->andReturn($from);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andReturn($agg);

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->sum($query, 'balance'));
    }

    public function testSumFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while getting the sum of Person balance');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->sum($query, 'balance');
    }

    public function testAverage()
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
        $agg = Mockery::mock();
        $agg->shouldReceive('average')
            ->withArgs(['balance'])
            ->andReturn($from);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andReturn($agg);

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->average($query, 'balance'));
    }

    public function testAverageFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while getting the sum of Person balance');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->average($query, 'balance');
    }

    public function testMax()
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
        $agg = Mockery::mock();
        $agg->shouldReceive('max')
            ->withArgs(['balance'])
            ->andReturn($from);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andReturn($agg);

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->max($query, 'balance'));
    }

    public function testMaxFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while getting the max of Person balance');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->max($query, 'balance');
    }

    public function testMin()
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
        $agg = Mockery::mock();
        $agg->shouldReceive('min')
            ->withArgs(['balance'])
            ->andReturn($from);
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andReturn($agg);

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals(1, $driver->min($query, 'balance'));
    }

    public function testMinFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while getting the min of Person balance');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->min($query, 'balance');
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

        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);

        $this->assertEquals([['test' => true]], $driver->queryModels($query));
    }

    public function testQueryModelsFail()
    {
        $this->expectException(DriverException::class, 'An error occurred in the database driver while performing the Person query: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(QueryBuilder::class);
        $db->shouldReceive('select->from->where->limit->orderBy->all')
            ->andThrow(new PDOException('error'));
        $driver = new DatabaseDriver();
        $driver->setConnection($db);
        Person::setDriver($driver);
        $driver->queryModels($query);
    }
}
