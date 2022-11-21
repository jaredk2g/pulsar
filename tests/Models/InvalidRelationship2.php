<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class InvalidRelationship2 extends Model
{
    protected static function getProperties(): array
    {
        return [
            'invalid_relationship' => [
                'relation' => TestModel2::class,
                'relation_type' => 'not a valid type',
            ],
        ];
    }
}
