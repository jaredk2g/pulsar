<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class RelationshipTester extends Model
{
    protected static $properties = [
        'belongs_to' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
        'belongs_to_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO_MANY,
        ],
        'has_one' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_HAS_ONE,
        ],
        'has_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
        'name' => [],
    ];
}
