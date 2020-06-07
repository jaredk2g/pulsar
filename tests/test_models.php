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

function validate()
{
    return false;
}

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
            'mutable' => Model::MUTABLE_CREATE_ONLY,
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
        if ('whatever' == $permission) {
            // always say no the first time
            if ($this->first) {
                $this->first = false;

                return false;
            }

            return true;
        } elseif ('do nothing' == $permission) {
            return 5 == $requester->id();
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
            'type' => Type::INTEGER,
        ],
    ];
}

class Category extends Model
{
    protected static $properties = [
        'name' => [],
        'posts' => [
            'relation' => Post::class,
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
    ];
}

class Garage extends Model
{
    protected static $properties = [
        'person_id' => [
            'type' => Type::INTEGER,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
        'location' => [],
        'cars' => [
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
    ];
}

class Car extends Model
{
    protected static $properties = [
        'make' => [],
        'model' => [],
        'garage_id' => [
            'type' => Type::INTEGER,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
    ];
}

class Balance extends Model
{
    protected static $properties = [
        'person_id' => [
            'type' => Type::INTEGER,
            'relation' => Person::class,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
        'amount' => [
            'type' => Type::FLOAT,
        ],
    ];
}

class RelationshipTester extends Model
{
    protected static $properties = [
        'belongs_to' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO,
        ],
        'belongs_to_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_BELONGS_TO_MANY,
        ],
        'has_one' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_HAS_ONE,
        ],
        'has_many' => [
            'relation' => TestModel2::class,
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
        'name' => [],
    ];
}

class InvalidRelationship extends Model
{
    protected static $properties = [
        'name' => [],
    ];
}

class InvalidRelationship2 extends Model
{
    protected static $properties = [
        'invalid_relationship' => [
            'relation' => TestModel2::class,
            'relation_type' => 'not a valid type',
        ],
    ];
}

class TransactionModel extends Model
{
    protected static $properties = [
        'name' => [
            'required' => true,
            'validate' => 'string:5',
        ],
    ];

    protected function initialize()
    {
        parent::initialize();

        self::deleting(function (\Pulsar\ModelEvent $modelEvent) {
            if ('delete fail' == $modelEvent->getModel()->name) {
                $modelEvent->stopPropagation();
            }
        });
    }

    protected function usesTransactions(): bool
    {
        return true;
    }
}
