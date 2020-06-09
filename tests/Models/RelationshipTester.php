<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;

class RelationshipTester extends Model
{
    protected static $properties = [
        'belongs_to' => [
            'relation' => TestModel2::class,
            'relation_type' => Relationship::BELONGS_TO,
        ],
        'belongs_to_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Relationship::BELONGS_TO_MANY,
        ],
        'has_one' => [
            'relation' => TestModel2::class,
            'relation_type' => Relationship::HAS_ONE,
        ],
        'has_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Relationship::HAS_MANY,
        ],
        'name' => [],
    ];
}
