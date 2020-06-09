<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;
use Pulsar\Type;

class Car extends Model
{
    protected static $properties = [
        'make' => [],
        'model' => [],
        'garage_id' => [
            'type' => Type::INTEGER,
            'relation_type' => Relationship::BELONGS_TO,
        ],
    ];
}
