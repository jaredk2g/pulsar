<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Group extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => [],
        ];
    }
}
