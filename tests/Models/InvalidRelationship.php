<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class InvalidRelationship extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => [],
        ];
    }
}
