<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Garage extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => new Property([
                'belongs_to' => Person::class,
            ]),
            'location' => new Property(),
            'cars' => new Property([
                'has_many' => Car::class,
            ]),
        ];
    }
}
