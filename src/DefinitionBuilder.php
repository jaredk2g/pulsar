<?php

namespace Pulsar;

use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\Relationship;

final class DefinitionBuilder
{
    const DEFAULT_ID_PROPERTY = [
        'type' => Type::INTEGER,
        'mutable' => Property::IMMUTABLE,
    ];

    const AUTO_TIMESTAMPS = [
        'created_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
        'updated_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
    ];

    const SOFT_DELETE_TIMESTAMPS = [
        'deleted_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
            'null' => true,
        ],
    ];

    /** @var Definition[] */
    private static $definitions;

    /**
     * Gets the definition for a model. If needed the
     * definition will be generated. It will only be
     * generated once.
     */
    public static function get(string $modelClass): Definition
    {
        /** @var Model $modelClass */
        if (!isset(self::$definitions[$modelClass])) {
            self::$definitions[$modelClass] = $modelClass::buildDefinition();
        }

        return self::$definitions[$modelClass];
    }

    /**
     * Builds a model definition given certain parameters.
     */
    public static function build(array $properties, string $modelClass, bool $autoTimestamps, bool $softDelete): Definition
    {
        /** @var Model $modelClass */
        // add in the default ID property
        if (!isset($properties[Model::DEFAULT_ID_NAME]) && $modelClass::getIDProperties() == [Model::DEFAULT_ID_NAME]) {
            $properties[Model::DEFAULT_ID_NAME] = self::DEFAULT_ID_PROPERTY;
        }

        // generates created_at and updated_at timestamps
        if ($autoTimestamps) {
            $properties = array_replace(self::AUTO_TIMESTAMPS, $properties);
        }

        // generates deleted_at timestamps
        if ($softDelete) {
            $properties = array_replace(self::SOFT_DELETE_TIMESTAMPS, $properties);
        }

        $result = [];
        foreach ($properties as $k => $property) {
            // populate relationship property settings
            if (isset($property['relation'])) {
                // this is added for BC with older versions of pulsar
                // that only supported belongs to relationships
                if (!isset($property['relation_type'])) {
                    $property['relation_type'] = Relationship::BELONGS_TO;
                    $property['local_key'] = $property['local_key'] ?? $k;
                } elseif (!isset($property['persisted'])) {
                    $property['persisted'] = false;
                }

                $tempProperty = new Property($property);
                $relation = Relationship::make(new $modelClass(), $k, $tempProperty);
                if (!isset($property['foreign_key'])) {
                    $property['foreign_key'] = $relation->getForeignKey();
                }

                if (!isset($property['local_key'])) {
                    $property['local_key'] = $relation->getLocalKey();
                }

                if (!isset($property['pivot_tablename']) && $relation instanceof BelongsToMany) {
                    $property['pivot_tablename'] = $relation->getTablename();
                }

                // when a belongs_to relationship is used then we automatically add a
                // new property for the ID field which gets persisted to the DB
                if (Relationship::BELONGS_TO == $property['relation_type'] && !isset($result[$property['local_key']])) {
                    $result[$property['local_key']] = new Property([
                        'type' => Type::INTEGER,
                    ], $property['local_key']);
                }
            }

            $result[$k] = new Property($property, $k);
        }

        // order the properties array by name for consistency
        // since it is constructed in a random order
        ksort($result);

        return new Definition($result);
    }
}
