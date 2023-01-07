<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Traits\SoftDelete;
use Pulsar\Type;

class Person extends ACLModel
{
    use SoftDelete;

    protected static function getProperties(): array
    {
        return [
            'id' => new Property(
                type: Type::STRING,
            ),
            'name' => new Property(
                type: Type::STRING,
                default: 'Jared',
            ),
            'email' => new Property(
                type: Type::STRING,
                validate: 'email',
            ),
            'garage' => new Property(
                has_one: Garage::class,
            ),
        ];
    }

    protected function hasPermission(string $permission, Model $requester): bool
    {
        return false;
    }
}
