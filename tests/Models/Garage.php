<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;
use Pulsar\Type;

class Garage extends Model
{
    protected static $properties = [
        'person_id' => [
            'type' => Type::INTEGER,
            'relation_type' => Relationship::BELONGS_TO,
        ],
        'location' => [],
        'cars' => [
            'relation_type' => Relationship::HAS_MANY,
        ],
    ];
}
