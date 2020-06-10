<?php

namespace Pulsar\Relation;

use InvalidArgumentException;
use Pulsar\Model;
use Pulsar\Property;

final class Relationship
{
    const HAS_ONE = 'has_one';
    const HAS_MANY = 'has_many';
    const BELONGS_TO = 'belongs_to';
    const BELONGS_TO_MANY = 'belongs_to_many';

    /**
     * Creates a new relation instance given a model and property.
     */
    public static function make(Model $model, Property $property): AbstractRelation
    {
        $foreignModel = $property->getForeignModelClass();
        if (!$foreignModel) {
            throw new InvalidArgumentException('Property "'.$property->getName().'" does not have a relationship.');
        }

        $foreignKey = $property->getForeignKey();
        $localKey = $property->getLocalKey();
        $type = $property->getRelationshipType();

        if (self::BELONGS_TO == $type) {
            return new BelongsTo($model, $localKey, $foreignModel, $foreignKey);
        }

        if (self::BELONGS_TO_MANY == $type) {
            $pivotTable = $property->getPivotTablename();

            return new BelongsToMany($model, $localKey, $pivotTable, $foreignModel, $foreignKey);
        }

        if (self::HAS_ONE == $type) {
            return new HasOne($model, $localKey, $foreignModel, $foreignKey);
        }

        if (self::HAS_MANY == $type) {
            return new HasMany($model, $localKey, $foreignModel, $foreignKey);
        }

        throw new InvalidArgumentException('Relationship type on "'.$property->getName().'" property not supported: '.$type);
    }
}
