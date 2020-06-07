<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;
use Pulsar\Type;

class Person extends ACLModel
{
    protected static $properties = [
        'id' => [
            'type' => Type::STRING,
        ],
        'name' => [
            'type' => Type::STRING,
            'default' => 'Jared',
        ],
        'email' => [
            'type' => Type::STRING,
            'validate' => 'email',
        ],
        'garage' => [
            'relation' => Garage::class,
            'relation_type' => Model::RELATIONSHIP_HAS_ONE,
        ],
    ];

    protected static $softDelete;

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}
