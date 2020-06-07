<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class RelationshipTestModel extends Model
{
    protected static $appended = ['person'];

    protected function getPersonValue()
    {
        return new Person(10, ['name' => 'Bob Loblaw', 'email' => 'bob@example.com']);
    }
}
