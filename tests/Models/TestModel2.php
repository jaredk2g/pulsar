<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Query;
use Pulsar\Traits\AutoTimestamps;
use Pulsar\Type;

class TestModel2 extends Model
{
    use AutoTimestamps;

    protected static $ids = ['id', 'id2'];

    protected static $properties = [
        'id' => [
            'type' => Type::INTEGER,
        ],
        'id2' => [
            'type' => Type::INTEGER,
        ],
        'default' => [
            'default' => 'some default value',
        ],
        'validate' => [
            'validate' => ['email', ['string', 'min' => 5]],
            'null' => true,
        ],
        'validate2' => [
            'validate' => ['callable', 'fn' => 'modelValidate', 'field' => 'validate2'],
            'null' => true,
            'in_array' => false,
        ],
        'unique' => [
            'validate' => ['unique', 'column' => 'unique'],
        ],
        'required' => [
            'type' => Type::INTEGER,
            'required' => true,
        ],
        'hidden' => [
            'type' => Type::BOOLEAN,
            'default' => false,
            'in_array' => false,
        ],
        'person' => [
            'type' => Type::INTEGER,
            'relation' => Person::class,
            'default' => 20,
            'in_array' => false,
        ],
        'array' => [
            'type' => Type::ARRAY,
            'default' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
            'in_array' => false,
        ],
        'object' => [
            'type' => Type::OBJECT,
            'in_array' => false,
        ],
        'mutable_create_only' => [
            'mutable' => Property::MUTABLE_CREATE_ONLY,
            'in_array' => false,
        ],
        'protected' => [],
    ];

    public static $query;

    public static function query(): Query
    {
        if ($query = self::$query) {
            self::$query = false;

            return $query;
        }

        return parent::query();
    }

    public static function setQuery(Query $query)
    {
        self::$query = $query;
    }

    protected function getMassAssignmentBlacklist(): ?array
    {
        return ['protected'];
    }
}
