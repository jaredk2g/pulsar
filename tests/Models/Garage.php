<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Garage extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => [
                'belongs_to' => Person::class,
            ],
            'location' => [],
            'cars' => [
                'has_many' => Car::class,
            ],
        ];
    }
}
