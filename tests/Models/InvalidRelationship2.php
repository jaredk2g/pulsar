<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class InvalidRelationship2 extends Model
{
    protected static function getProperties(): array
    {
        return [
            'invalid_relationship' => new Property([
                'relation' => TestModel2::class,
                'relation_type' => 'not a valid type',
            ]),
        ];
    }
}
