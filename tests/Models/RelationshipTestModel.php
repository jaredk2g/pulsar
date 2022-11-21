<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class RelationshipTestModel extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => [
                'persisted' => false,
                'in_array' => true,
            ],
        ];
    }

    protected function getPersonValue()
    {
        return new Person(['id' => 10, 'name' => 'Bob Loblaw', 'email' => 'bob@example.com']);
    }
}
