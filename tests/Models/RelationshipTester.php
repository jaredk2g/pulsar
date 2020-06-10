<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class RelationshipTester extends Model
{
    protected static $properties = [
        'belongs_to_legacy' => [
            'relation' => TestModel2::class,
        ],
        'belongs_to' => [
            'belongs_to' => TestModel2::class,
        ],
        'belongs_to_many' => [
            'belongs_to_many' => TestModel2::class,
        ],
        'has_one' => [
            'has_one' => TestModel2::class,
        ],
        'has_many' => [
            'has_many' => TestModel2::class,
        ],
        'name' => [],
    ];
}
