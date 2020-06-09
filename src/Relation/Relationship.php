<?php

namespace Pulsar\Relation;

use InvalidArgumentException;
use Pulsar\Model;
use Pulsar\Property;

class Relationship
{
    const HAS_ONE = 'has_one';
    const HAS_MANY = 'has_many';
    const BELONGS_TO = 'belongs_to';
    const BELONGS_TO_MANY = 'belongs_to_many';

    /**
     * Creates a new relation instance given a model and property.
     */
    public static function make(Model $model, string $propertyName, Property $property): AbstractRelation
    {
        $relationModelClass = $property->getRelation();
        if (!$relationModelClass) {
            throw new InvalidArgumentException('Property "'.$propertyName.'" does not have a relationship.');
        }

        $foreignKey = $property->getForeignKey();
        $localKey = $property->getLocalKey();
        $relationType = $property->getRelationType();

        if (self::HAS_ONE == $relationType) {
            return new HasOne($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (self::HAS_MANY == $relationType) {
            return new HasMany($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (self::BELONGS_TO == $relationType) {
            return new BelongsTo($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (self::BELONGS_TO_MANY == $relationType) {
            $pivotTable = $property->getPivotTablename();

            return new BelongsToMany($model, $localKey, $pivotTable, $relationModelClass, $foreignKey);
        }

        throw new InvalidArgumentException('Relationship type on "'.$propertyName.'" property not supported: '.$relationType);
    }
}
