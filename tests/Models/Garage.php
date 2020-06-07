<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Type;

class Garage extends Model
{
    protected static $properties = [
        'person_id' => [
            'type' => Type::INTEGER,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
        'location' => [],
        'cars' => [
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
    ];
}
