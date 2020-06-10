<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Car extends Model
{
    protected static $properties = [
        'make' => [],
        'model' => [],
        'garage' => [
            'belongs_to' => Garage::class,
        ],
    ];
}
