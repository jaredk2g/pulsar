<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Group extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => new Property(),
        ];
    }
}
