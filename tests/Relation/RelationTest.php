<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Relation\Relation;

class RelationTest extends MockeryTestCase
{
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
        $this->assertEquals(['test' => true], $query->getWhere());
    }

    public function testCallOnQuery()
    {
        $model = Mockery::mock(Model::class);
        $relation = new DistantRelation($model, 'user_id', 'TestModel', 'id');

        $query = $relation->where(['name' => 'Bob']);

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(['test' => true, 'name' => 'Bob'], $query->getWhere());
    }
}

class DistantRelation extends Relation
{
    protected function initQuery(Query $query)
    {
        $query->where('test', true);

        return $query;
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
