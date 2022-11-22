<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Car extends Model
{
    protected static function getProperties(): array
    {
        return [
            'make' => new Property(),
            'model' => new Property(),
            'garage' => new Property([
                'belongs_to' => Garage::class,
            ]),
        ];
    }
}
