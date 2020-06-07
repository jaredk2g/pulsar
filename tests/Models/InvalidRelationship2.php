<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class InvalidRelationship2 extends Model
{
    protected static $properties = [
        'invalid_relationship' => [
            'relation' => TestModel2::class,
            'relation_type' => 'not a valid type',
        ],
    ];
}
