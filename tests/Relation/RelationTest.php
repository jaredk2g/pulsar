<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Relation\Relation;

class RelationTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $this->assertTrue($relation->initQuery);
    }

    public function testGetLocalModel()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testGetLocalKey()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $this->assertEquals('user_id', $relation->getLocalKey());
    }

    public function testGetForeignModel()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $this->assertEquals('TestModel', $relation->getForeignModel());
    }

    public function testGetForeignKey()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $this->assertEquals('id', $relation->getForeignKey());
    }

    public function testGetQuery()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $query = $relation->getQuery();
        $this->assertInstanceOf(Query::class, $query);
    }

    public function testCallOnQuery()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $relation->where(['name' => 'Bob']);

        $this->assertEquals(['name' => 'Bob'], $relation->getQuery()->getWhere());
    }
}

class DistantRelation extends Relation
{
    public $initQuery;

    protected function initQuery()
    {
        $this->initQuery = true;
    }

    public function getResults()
    {
        // do nothing
    }

    public function save(Model $model)
    {
        // do nothing
    }

    public function create(array $values = [])
    {
        // do nothing
    }
}
