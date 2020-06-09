<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;
use Pulsar\Type;

class Balance extends Model
{
    protected static $properties = [
        'person' => [
            'relation' => Person::class,
            'relation_type' => Relationship::BELONGS_TO,
        ],
        'amount' => [
            'type' => Type::FLOAT,
        ],
    ];
}
