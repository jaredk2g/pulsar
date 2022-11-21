<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Car extends Model
{
    protected static function getProperties(): array
    {
        return [
            'make' => [],
            'model' => [],
            'garage' => [
                'belongs_to' => Garage::class,
            ],
        ];
    }
}
