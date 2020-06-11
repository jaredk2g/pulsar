<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Query;
use Pulsar\Type;

class TestModel2 extends Model
{
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
            'validate' => ['callable', 'fn' => 'modelValidate'],
            'null' => true,
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
        ],
        'person' => [
            'type' => Type::INTEGER,
            'relation' => Person::class,
            'default' => 20,
        ],
        'array' => [
            'type' => Type::ARRAY,
            'default' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
        ],
        'object' => [
            'type' => Type::OBJECT,
        ],
        'mutable_create_only' => [
            'mutable' => Property::MUTABLE_CREATE_ONLY,
        ],
        'protected' => [],
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['validate2', 'hidden', 'person', 'array', 'object', 'mutable_create_only'];
    protected static $protected = ['protected'];

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
}
