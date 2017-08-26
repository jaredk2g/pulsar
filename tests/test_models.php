<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pulsar\ACLModel;
use Pulsar\Cacheable;
use Pulsar\Model;
use Pulsar\Query;

class TestModel extends Model
{
    protected static $properties = [
        'relation' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => TestModel2::class,
            'null' => true,
        ],
        'answer' => [
            'type' => Model::TYPE_STRING,
        ],
        'mutator' => [],
        'accessor' => [],
    ];
    public $preDelete;
    public $postDelete;

    protected static $hidden = ['mutator', 'accessor'];
    protected static $appended = ['appended'];
    protected static $permitted = ['id', 'relation', 'answer', 'mutator', 'accessor'];

    public static $query;

    public static $preSetHookValues;

    protected function initialize()
    {
        self::$properties['test_hook'] = [
            'type' => Model::TYPE_STRING,
            'null' => true,
        ];

        parent::initialize();
    }

    public static function query()
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

    protected function preSetHook(array &$data)
    {
        self::$preSetHookValues = $data;

        return true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        $result['toArrayHook'] = true;
    }
}

function validate()
{
    return false;
}

class TestModel2 extends Model
{
    protected static $ids = ['id', 'id2'];

    protected static $properties = [
        'id' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'id2' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'default' => [
            'default' => 'some default value',
        ],
        'validate' => [
            'validate' => 'email|string:5',
            'null' => true,
        ],
        'validate2' => [
            'validate' => 'validate',
            'null' => true,
        ],
        'unique' => [
            'unique' => true,
        ],
        'required' => [
            'type' => Model::TYPE_NUMBER,
            'required' => true,
        ],
        'hidden' => [
            'type' => Model::TYPE_BOOLEAN,
            'default' => false,
        ],
        'person' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => Person::class,
            'default' => 20,
        ],
        'array' => [
            'type' => Model::TYPE_ARRAY,
            'default' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
        ],
        'object' => [
            'type' => Model::TYPE_OBJECT,
        ],
        'mutable_create_only' => [
            'mutable' => Model::MUTABLE_CREATE_ONLY,
        ],
        'protected' => [],
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['validate2', 'hidden', 'person', 'array', 'object', 'mutable_create_only'];
    protected static $protected = ['protected'];

    public static $query;

    public static function query()
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

class TestModelNoPermission extends ACLModel
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class Person extends ACLModel
{
    protected static $properties = [
        'id' => [
            'type' => Model::TYPE_STRING,
        ],
        'name' => [
            'type' => Model::TYPE_STRING,
            'default' => 'Jared',
        ],
        'email' => [
            'type' => Model::TYPE_STRING,
            'validate' => 'email',
            'title' => 'Email address',
        ],
    ];

    protected static $softDelete;

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class Group extends Model
{
    protected static $properties = [
        'name' => [],
    ];
}

class IteratorTestModel extends Model
{
    protected static $properties = [
        'name' => [],
    ];
}

class AclObject extends ACLModel
{
    public $first = true;

    protected function hasPermission($permission, Model $requester)
    {
        if ($permission == 'whatever') {
            // always say no the first time
            if ($this->first) {
                $this->first = false;

                return false;
            }

            return true;
        } elseif ($permission == 'do nothing') {
            return $requester->id() == 5;
        }
    }
}

class CacheableModel extends Model
{
    use Cacheable;

    public static $cacheTTL = 10;
}

class RelationshipTestModel extends Model
{
    protected static $appended = ['person'];

    protected function getPersonValue()
    {
        return new Person(10, ['name' => 'Bob Loblaw', 'email' => 'bob@example.com']);
    }
}

class Post extends Model
{
    protected static $properties = [
        'category_id' => [
            'type' => Model::TYPE_INTEGER,
        ],
    ];
}

class Category extends Model
{
    protected static $properties = [
        'name' => [],
    ];
}

class Car extends Model
{
    protected static $properties = [
        'type' => [],
        'person_id' => [
            'type' => Model::TYPE_INTEGER,
        ],
    ];
}

class Balance extends Model
{
    protected static $properties = [
        'person_id' => [
            'type' => Model::TYPE_INTEGER,
        ],
        'amount' => [
            'type' => Model::TYPE_FLOAT,
        ],
    ];
}
