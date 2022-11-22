<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Query;
use Pulsar\Type;

class TestModel extends Model
{
    protected static $properties = [];
    public $preDelete;
    public $postDelete;

    public static $query;

    protected function initialize(): void
    {
        self::$properties['test_hook'] = [
            'type' => Type::STRING,
            'null' => true,
        ];

        parent::initialize();
    }

    protected static function getProperties(): array
    {
        return array_replace([
            'relation' => new Property([
                'type' => Type::INTEGER,
                'relation' => TestModel2::class,
                'null' => true,
            ]),
            'answer' => new Property([
                'type' => Type::STRING,
            ]),
            'mutator' => new Property([
                'in_array' => false,
            ]),
            'accessor' => new Property([
                'in_array' => false,
            ]),
            'encrypted' => new Property([
                'encrypted' => true,
            ]),
            'appended' => new Property([
                'persisted' => false,
                'in_array' => true,
            ]),
        ], self::$properties);
    }

    protected function getMassAssignmentWhitelist(): ?array
    {
        return ['id', 'relation', 'answer', 'mutator', 'accessor', 'fail'];
    }

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

    protected function setMutatorValue($value)
    {
        return strtoupper($value);
    }

    protected function getAccessorValue($value)
    {
        return strtolower($value);
    }

    protected function getAppendedValue()
    {
        return true;
    }
}
