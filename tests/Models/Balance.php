<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Type;

class Balance extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => [
                'belongs_to' => Person::class,
            ],
            'amount' => [
                'type' => Type::FLOAT,
            ],
        ];
    }
}
