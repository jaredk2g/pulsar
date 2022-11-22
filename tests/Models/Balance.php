<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Type;

class Balance extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => new Property([
                'belongs_to' => Person::class,
            ]),
            'amount' => new Property([
                'type' => Type::FLOAT,
            ]),
        ];
    }
}
