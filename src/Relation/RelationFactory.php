<?php

namespace Pulsar\Relation;

use InvalidArgumentException;
use Pulsar\Model;
use Pulsar\Property;

class RelationFactory
{
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
            return self::hasOne($model, $relationModelClass, $foreignKey, $localKey);
        }

        if (Model::RELATIONSHIP_HAS_MANY == $relationType) {
            return self::hasMany($model, $relationModelClass, $foreignKey, $localKey);
        }

        if (Model::RELATIONSHIP_BELONGS_TO == $relationType) {
            return self::belongsTo($model, $relationModelClass, $foreignKey, $localKey);
        }

        if (Model::RELATIONSHIP_BELONGS_TO_MANY == $relationType) {
            $pivotTable = $property->getPivotTablename();

            return self::belongsToMany($model, $relationModelClass, $pivotTable, $foreignKey, $localKey);
        }

        throw new InvalidArgumentException('Relationship type on "'.$propertyName.'" property not supported: '.$relationType);
    }

    /**
     * Creates the parent side of a One-To-One relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     */
    public static function hasOne(Model $theModel, $model, $foreignKey = '', $localKey = ''): HasOne
    {
        return new HasOne($theModel, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the child side of a One-To-One or One-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     */
    public static function belongsTo(Model $theModel, $model, $foreignKey = '', $localKey = ''): BelongsTo
    {
        return new BelongsTo($theModel, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the parent side of a Many-To-One or Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     */
    public static function hasMany(Model $theModel, $model, $foreignKey = '', $localKey = ''): HasMany
    {
        return new HasMany($theModel, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the child side of a Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $tablename  pivot table name
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     */
    public static function belongsToMany(Model $theModel, $model, $tablename = '', $foreignKey = '', $localKey = ''): BelongsToMany
    {
        return new BelongsToMany($theModel, $localKey, $tablename, $model, $foreignKey);
    }
}
