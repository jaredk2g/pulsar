<?php

namespace Pulsar\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pulsar\Relation\BelongsTo;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\HasMany;
use Pulsar\Relation\HasOne;
use Pulsar\Relation\Relationship;
use Pulsar\Tests\Models\InvalidRelationship;
use Pulsar\Tests\Models\InvalidRelationship2;
use Pulsar\Tests\Models\RelationshipTester;
use Pulsar\Tests\Models\TestModel2;

class RelationshipTest extends TestCase
{
    public function testNotRelationship()
    {
        $this->expectException(InvalidArgumentException::class);

        $model = new InvalidRelationship();
        Relationship::make($model, InvalidRelationship::getProperty('name'));
    }

    public function testInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

        $model = new InvalidRelationship();
        Relationship::make($model, InvalidRelationship2::getProperty('invalid_relationship'));
    }

    public function testHasOne()
    {
        $model = new RelationshipTester();
        $relation = Relationship::make($model, RelationshipTester::getProperty('has_one'));

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('relationship_tester_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testHasMany()
    {
        $model = new RelationshipTester();
        $relation = Relationship::make($model, RelationshipTester::getProperty('has_many'));

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('relationship_tester_id', $relation->getForeignKey());
        $this->assertEquals('id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsTo()
    {
        $model = new RelationshipTester();
        $relation = Relationship::make($model, RelationshipTester::getProperty('belongs_to'));

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('belongs_to_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsToLegacy()
    {
        $model = new RelationshipTester();
        $relation = Relationship::make($model, RelationshipTester::getProperty('belongs_to_legacy'));

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('id', $relation->getForeignKey());
        $this->assertEquals('belongs_to_legacy', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testBelongsToMany()
    {
        $model = new RelationshipTester();
        $relation = Relationship::make($model, RelationshipTester::getProperty('belongs_to_many'));

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(TestModel2::class, $relation->getForeignModel());
        $this->assertEquals('test_model2_id', $relation->getForeignKey());
        $this->assertEquals('relationship_tester_id', $relation->getLocalKey());
        $this->assertEquals($model, $relation->getLocalModel());
        $this->assertEquals('RelationshipTesterTestModel2', $relation->getTablename());
    }
}
