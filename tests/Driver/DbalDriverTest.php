<?php

namespace Pulsar\Tests\Driver;

/*
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Driver\DbalDriver;
use Pulsar\Exception\DriverException;
use Pulsar\Query;
use Pulsar\Tests\Models\Group;
use Pulsar\Tests\Models\Person;
use stdClass;

class DbalDriverTest extends MockeryTestCase
{
    private function getDriver($connection = null): DbalDriver
    {
        $connection = $connection ? $connection : Mockery::mock(Connection::class);

        return new DbalDriver($connection);
    }

    public function testGetConnection()
    {
        $db = Mockery::mock(Connection::class);
        $driver = $this->getDriver($db);
        $this->assertEquals($db, $driver->getConnection(null));
    }

    public function testGetConnectionFromManagerMissing()
    {
        $this->expectException(DriverException::class);
        $this->getDriver()->getConnection('not_supported');
    }

    public function testSerializeValue()
    {
        $driver = $this->getDriver();

        $this->assertEquals('string', $driver->serializeValue('string'));

        $arr = ['test' => true];
        $this->assertEquals('{"test":true}', $driver->serializeValue($arr));

        $obj = new stdClass();
        $obj->test = true;
        $this->assertEquals('{"test":true}', $driver->serializeValue($obj));
    }

    public function testCreateModel()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->withArgs(['INSERT INTO `People` (`answer`, `array`) VALUES (?, ?)', [0 => 42, 1 => '{"test":true}']])
            ->once();

        $driver = $this->getDriver($db);

        $model = new Person();
        $this->assertTrue($driver->createModel($model, ['answer' => 42, 'array' => ['test' => true]]));
    }

    public function testCreateModelFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver when creating the Person: error');
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $model = new Person();
        $driver->createModel($model, []);
    }

    public function testGetCreatedID()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('lastInsertId')
            ->andReturn('1');

        $driver = $this->getDriver($db);

        $model = new Person();
        $this->assertEquals(1, $driver->getCreatedID($model, 'id'));
    }

    public function testGetCreatedIDFail()
    {
        $this->expectException(DriverException::class);
        $this->matchesRegularExpression('An error occurred in the database driver when getting the ID of the new Person: error');
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('lastInsertId')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $model = new Person();
        $driver->getCreatedID($model, 'id');
    }

    public function testLoadModel()
    {
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchAssoc')
            ->withArgs(['SELECT * FROM `People` WHERE `id` = ?', [0 => '12']])
            ->andReturn(['name' => 'John']);

        $driver = $this->getDriver($db);

        $model = new Person(['id' => 12]);
        $this->assertEquals(['name' => 'John'], $driver->loadModel($model));
    }

    public function testLoadModelNotFound()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchAssoc')
            ->andReturn(null);
        $driver = $this->getDriver($db);

        $model = new Person(['id' => 12]);
        $this->assertNull($driver->loadModel($model));
    }

    public function testLoadModelFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver when loading an instance of Person: error');
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchAssoc')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $model = new Person(['id' => 12]);
        $driver->loadModel($model);
    }

    public function testQueryModels()
    {
        $query = new Query(Person::class);
        $query->where('id', 50, '>')
            ->where(['city' => 'Austin'])
            ->where('RAW SQL')
            ->where('People.alreadyDotted', true)
            ->join(Group::class, 'group', 'id')
            ->sort('name asc')
            ->limit(5)
            ->start(10);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchAll')
            ->withArgs(['SELECT `People`.* FROM `People` JOIN `Groups` ON People.group=Groups.id WHERE `People`.`id` > ? AND `People`.`city` = ? AND RAW SQL AND `People`.`alreadyDotted` = ? ORDER BY `People`.`name` asc LIMIT 10,5', [0 => 50, 1 => 'Austin', 2 => true]])
            ->andReturn([['test' => true]]);

        $driver = $this->getDriver($db);

        $this->assertEquals([['test' => true]], $driver->queryModels($query));
    }

    public function testQueryModelsFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while performing the Person query: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchAll')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->queryModels($query);
    }

    public function testUpdateModel()
    {
        // update query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->withArgs(['UPDATE `People` SET `name` = ?, `array` = ? WHERE `id` = ?', [0 => 'John', 1 => '{"test":true}', 2 => '11']])
            ->once();

        $driver = $this->getDriver($db);

        $model = new Person(['id' => 11]);

        $this->assertTrue($driver->updateModel($model, []));

        $parameters = ['name' => 'John', 'array' => ['test' => true]];
        $this->assertTrue($driver->updateModel($model, $parameters));
    }

    public function testUpdateModelFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver when updating the Person: error');
        // update query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $model = new Person(['id' => 11]);
        $driver->updateModel($model, ['name' => 'John']);
    }

    public function testDeleteModel()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->withArgs(['DELETE FROM `People` WHERE `id` = ?', [0 => '10']])
            ->once();

        $driver = $this->getDriver($db);

        $model = new Person(['id' => 10]);
        $this->assertTrue($driver->deleteModel($model));
    }

    public function testDeleteModelFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while deleting the Person: error');
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('executeQuery')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $model = new Person(['id' => 10]);
        $driver->deleteModel($model);
    }

    public function testCount()
    {
        $query = new Query(Person::class);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->withArgs(['SELECT COUNT(*) FROM `People`', []])
            ->andReturn(1);

        $driver = $this->getDriver($db);

        $this->assertEquals(1, $driver->count($query));
    }

    public function testCountFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while getting the value of Person.count: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->count($query);
    }

    public function testSum()
    {
        $query = new Query(Person::class);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->withArgs(['SELECT SUM(People.balance) FROM `People`', []])
            ->andReturn(1);

        $driver = $this->getDriver($db);

        $this->assertEquals(1, $driver->sum($query, 'balance'));
    }

    public function testSumFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while getting the value of Person.Person.balance: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->sum($query, 'Person.balance');
    }

    public function testAverage()
    {
        $query = new Query(Person::class);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->withArgs(['SELECT AVG(People.balance) FROM `People`', []])
            ->andReturn(1);

        $driver = $this->getDriver($db);

        $this->assertEquals(1, $driver->average($query, 'balance'));
    }

    public function testAverageFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while getting the value of Person.balance: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->average($query, 'balance');
    }

    public function testMax()
    {
        $query = new Query(Person::class);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->withArgs(['SELECT MAX(People.balance) FROM `People`', []])
            ->andReturn(1);

        $driver = $this->getDriver($db);

        $this->assertEquals(1, $driver->max($query, 'balance'));
    }

    public function testMaxFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while getting the value of Person.balance: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->max($query, 'balance');
    }

    public function testMin()
    {
        $query = new Query(Person::class);

        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->withArgs(['SELECT MIN(People.balance) FROM `People`', []])
            ->andReturn(1);

        $driver = $this->getDriver($db);

        $this->assertEquals(1, $driver->min($query, 'balance'));
    }

    public function testMinFail()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('An error occurred in the database driver while getting the value of Person.balance: error');
        $query = new Query(new Person());
        // select query mock
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('fetchColumn')
            ->andThrow(new DBALException('error'));
        $driver = $this->getDriver($db);
        $driver->min($query, 'balance');
    }

    public function testStartTransaction()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('beginTransaction')
            ->once();
        $driver = $this->getDriver($db);
        $driver->startTransaction(null);
    }

    public function testRollBackTransaction()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('rollBack')
            ->once();
        $driver = $this->getDriver($db);
        $driver->rollBackTransaction(null);
    }

    public function testCommitTransaction()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('commit')
            ->once();
        $driver = $this->getDriver($db);
        $driver->commitTransaction(null);
    }

    public function testNestedTransactions()
    {
        $db = Mockery::mock(Connection::class);
        $db->shouldReceive('beginTransaction')
            ->times(3);
        $db->shouldReceive('rollBack')
            ->once();
        $db->shouldReceive('commit')
            ->twice();
        $driver = $this->getDriver($db);

        $driver->startTransaction(null);
        $driver->startTransaction(null);
        $driver->startTransaction(null);
        $driver->commitTransaction(null);
        $driver->commitTransaction(null);
        $driver->rollBackTransaction(null);
    }
}
