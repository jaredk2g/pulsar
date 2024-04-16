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

    public static $query;

    public static function getIDProperties(): array
    {
        return ['id', 'id2'];
    }

    protected static function getProperties(): array
    {
        return [
            'id' => new Property(
                type: Type::INTEGER,
            ),
            'id2' => new Property(
                type: Type::INTEGER,
            ),
            'default' => new Property(
                default: 'some default value',
            ),
            'validate' => new Property(
                null: true,
                validate: ['email', ['string', 'min' => 5]],
            ),
            'validate2' => new Property(
                null: true,
                validate: ['callable', 'fn' => 'modelValidate', 'field' => 'validate2'],
                in_array: false,
            ),
            'unique' => new Property(
                validate: ['unique', 'column' => 'unique'],
            ),
            'required' => new Property(
                type: Type::INTEGER,
                required: true,
            ),
            'hidden' => new Property(
                type: Type::BOOLEAN,
                default: false,
                in_array: false,
            ),
            'person' => new Property(
                type: Type::INTEGER,
                default: 20,
                in_array: false,
                relation: Person::class,
            ),
            'array' => new Property(
                type: Type::ARRAY,
                default: [
                    'tax' => '%',
                    'discounts' => false,
                    'shipping' => false,
                ],
                in_array: false,
            ),
            'object' => new Property(
                type: Type::OBJECT,
                in_array: false,
            ),
            'mutable_create_only' => new Property(
                mutable: Property::MUTABLE_CREATE_ONLY,
                in_array: false,
            ),
            'protected' => new Property(),
        ];
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

    protected function getMassAssignmentProtected(): ?array
    {
        return ['protected'];
    }
}
