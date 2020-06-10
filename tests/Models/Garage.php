<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Garage extends Model
{
    protected static $properties = [
        'person' => [
            'belongs_to' => Person::class,
        ],
        'location' => [],
        'cars' => [
            'has_many' => Car::class,
        ],
    ];
}
