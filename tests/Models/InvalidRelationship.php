<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class InvalidRelationship extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => new Property(),
        ];
    }
}
