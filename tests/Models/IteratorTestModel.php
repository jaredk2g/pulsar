<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class IteratorTestModel extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => [],
        ];
    }
}
