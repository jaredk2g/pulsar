<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Type;

class TestModel extends Model
{
    protected static $properties = [
        'relation' => [
            'type' => Type::INTEGER,
            'relation' => TestModel2::class,
            'null' => true,
        ],
        'answer' => [
            'type' => Type::STRING,
        ],
        'mutator' => [
            'in_array' => false,
        ],
        'accessor' => [
            'in_array' => false,
        ],
        'encrypted' => [
            'encrypted' => true,
        ],
        'appended' => [
            'persisted' => false,
            'in_array' => true,
        ],
    ];
    public $preDelete;
    public $postDelete;

    protected static $appended = ['appended_legacy'];

    public static $query;

    protected function initialize()
    {
        self::$properties['test_hook'] = [
            'type' => Type::STRING,
            'null' => true,
        ];

        parent::initialize();
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

    protected function getAppendedLegacyValue()
    {
        return true;
    }
}
