<?php

namespace Pulsar\Tests;

use InvalidArgumentException;
use InvalidRelationship;
use PHPUnit\Framework\TestCase;
use Pulsar\Relation\BelongsTo;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\HasMany;
use Pulsar\Relation\HasOne;
use Pulsar\Relation\RelationFactory;
use RelationshipTester;
use TestModel2;

class RelationFactoryTest extends TestCase
{
    public function testNotRelationship()
    {
        $this->expectException(InvalidArgumentException::class);

        $model = new InvalidRelationship();
        RelationFactory::make($model, 'name', InvalidRelationship::getProperty('name'));
    }

    public function testHasOne()
    {
        $model = new RelationshipTester();
        $relation = RelationFactory::make($model, 'has_one', RelationshipTester::getProperty('has_one'));

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('relationship_tester_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testHasMany()
    {
        $model = new RelationshipTester();
        $relation = RelationFactory::make($model, 'has_many', RelationshipTester::getProperty('has_many'));

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('relationship_tester_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsTo()
    {
        $model = new RelationshipTester();
        $relation = RelationFactory::make($model, 'belongs_to', RelationshipTester::getProperty('belongs_to'));

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('test_model2_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsToMany()
    {
        $model = new RelationshipTester();
        $relation = RelationFactory::make($model, 'belongs_to_many', RelationshipTester::getProperty('belongs_to_many'));

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('test_model2_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
        $this->assertEquals('RelationshipTesterTestModel2', $relation->getTablename());
    }
}
