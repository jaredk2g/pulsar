<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Type;

class Balance extends Model
{
    protected static $properties = [
        'person' => [
            'belongs_to' => Person::class,
        ],
        'amount' => [
            'type' => Type::FLOAT,
        ],
    ];
}
