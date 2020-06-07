<?php

namespace Pulsar\Relation;

use InvalidArgumentException;
use Pulsar\Model;
use Pulsar\Property;

class RelationFactory
{
    /**
     * Creates a new relation instance given a model and property.
     */
    public static function make(Model $model, string $propertyName, Property $property): Relation
    {
        $relationModelClass = $property->getRelation();
        if (!$relationModelClass) {
            throw new InvalidArgumentException('Property "'.$propertyName.'" does not have a relationship.');
        }

        $foreignKey = $property->getForeignKey();
        $localKey = $property->getLocalKey();
        $relationType = $property->getRelationType();

        if (Model::RELATIONSHIP_HAS_ONE == $relationType) {
            return new HasOne($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (Model::RELATIONSHIP_HAS_MANY == $relationType) {
            return new HasMany($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (Model::RELATIONSHIP_BELONGS_TO == $relationType) {
            return new BelongsTo($model, $localKey, $relationModelClass, $foreignKey);
        }

        if (Model::RELATIONSHIP_BELONGS_TO_MANY == $relationType) {
            $pivotTable = $property->getPivotTablename();

            return new BelongsToMany($model, $localKey, $pivotTable, $relationModelClass, $foreignKey);
        }

        throw new InvalidArgumentException('Relationship type on "'.$propertyName.'" property not supported: '.$relationType);
    }
}
