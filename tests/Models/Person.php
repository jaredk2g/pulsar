<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;
use Pulsar\Traits\SoftDelete;
use Pulsar\Type;

class Person extends ACLModel
{
    use SoftDelete;

    protected static function getProperties(): array
    {
        return [
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
                'has_one' => Garage::class,
            ],
        ];
    }

    protected function hasPermission($permission, Model $requester): bool
    {
        return false;
    }
}
