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
        'mutator' => [],
        'accessor' => [],
        'encrypted' => [
            'encrypted' => true,
        ],
    ];
    public $preDelete;
    public $postDelete;

    protected static $hidden = ['mutator', 'accessor'];
    protected static $appended = ['appended'];
    protected static $permitted = ['id', 'relation', 'answer', 'mutator', 'accessor', 'fail'];

    public static $query;

    protected function initialize()
    {
        self::$properties['test_hook'] = [
            'type' => Type::STRING,
            'null' => true,
        ];

        parent::initialize();
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
