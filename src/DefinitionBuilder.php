<?php

namespace Pulsar;

use ICanBoogie\Inflector;
use Pulsar\Relation\Relationship;

final class DefinitionBuilder
{
    const DEFAULT_ID_PROPERTY = [
        'type' => Type::INTEGER,
        'mutable' => Property::IMMUTABLE,
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
     *
     * @param string[] $ids
     */
    public static function build(array $ids, array $properties, string $modelClass): Definition
    {
        // add in the default ID property
        if (!isset($properties[Model::DEFAULT_ID_NAME]) && $ids == [Model::DEFAULT_ID_NAME]) {
            $properties[Model::DEFAULT_ID_NAME] = self::DEFAULT_ID_PROPERTY;
        }

        $result = [];
        foreach ($properties as $k => $property) {
            // convert to an array in order to fill in additional settings
            if ($property instanceof Property) {
                $hasDefault = $property->hasDefault();
                $property = $property->toArray();
                if (!$hasDefault) {
                    unset($property['default']);
                }
            }

            // handle relationship shortcuts
            $relationType = $property['relation_type'] ?? null;
            if (isset($property['relation']) && !$relationType) {
                self::buildBelongsToLegacy($k, $property);
            } elseif (isset($property['belongs_to']) || $relationType == Relationship::BELONGS_TO) {
                self::buildBelongsTo($k, $property, $result);
            } elseif (isset($property['has_one']) || $relationType == Relationship::HAS_ONE) {
                self::buildHasOne($property, $modelClass);
            } elseif (isset($property['belongs_to_many']) || $relationType == Relationship::BELONGS_TO_MANY) {
                self::buildBelongsToMany($property, $modelClass);
            } elseif (isset($property['has_many']) || $relationType == Relationship::HAS_MANY) {
                self::buildHasMany($property, $modelClass);
            } elseif (isset($property['morphs_to'])) {
                self::buildPolymorphic($property, $k);
            }

            // install validation rule for encrypted properties
            if (isset($property['encrypted']) && $property['encrypted'] && !isset($property['validate'])) {
                $property['validate'] = 'encrypt';
            }

            $property['name'] = $k;
            $result[$k] = new Property($property);
        }

        // order the properties array by name for consistency
        // since it is constructed in a random order
        ksort($result);

        return new Definition($ids, $result);
    }

    /////////////////////////////////
    // Relationship Shortcuts
    /////////////////////////////////

    /**
     * This is added for BC with older versions of pulsar
     * that only supported belongs to relationships.
     */
    private static function buildBelongsToLegacy(string $name, array &$property): void
    {
        $property['relation_type'] = Relationship::BELONGS_TO;
        // in the legacy configuration the default local key was the property name
        $property['local_key'] = $property['local_key'] ?? $name;

        // the default foreign key is `id`
        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }
    }

    private static function buildBelongsTo(string $name, array &$property, array &$result): void
    {
        // the default local key would look like `user_id`
        // for a property named `user`
        if (!isset($property['local_key'])) {
            $property['local_key'] = $name.'_id';
        }

        // the default foreign key is `id`
        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }

        // when a belongs_to relationship is used then we automatically add a
        // new property for the ID field which gets persisted to the DB
        if (!isset($result[$property['local_key']])) {
            $result[$property['local_key']] = new Property([
                'type' => Type::INTEGER,
                'mutable' => $property['mutable'] ?? Property::MUTABLE,
                'name' => $property['local_key'],
            ]);
        }
    }

    private static function buildBelongsToMany(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */

        // the default local key would look like `user_id`
        // for a model named User
        if (!isset($property['local_key'])) {
            $inflector = Inflector::get();
            $property['local_key'] = strtolower($inflector->underscore($modelClass::modelName())).'_id';
        }

        if (!isset($property['foreign_key'])) {
            $inflector = Inflector::get();
            $property['foreign_key'] = strtolower($inflector->underscore($property['relation']::modelName())).'_id';
        }

        // the default pivot table name looks like
        // RoleUser for models named Role and User.
        // the tablename is built from the model names
        // in alphabetic order.
        if (!isset($property['pivot_tablename'])) {
            $names = [
                $modelClass::modelName(),
                $property['relation']::modelName(),
            ];
            sort($names);
            $property['pivot_tablename'] = implode($names);
        }
    }

    private static function buildHasOne(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */

        // the default foreign key would look like `user_id`
        // for a model named User
        if (!isset($property['foreign_key'])) {
            $inflector = Inflector::get();
            $property['foreign_key'] = strtolower($inflector->underscore($modelClass::modelName())).'_id';
        }

        if (!isset($property['local_key'])) {
            $property['local_key'] = Model::DEFAULT_ID_NAME;
        }
    }

    private static function buildHasMany(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */

        // the default foreign key would look like
        // `user_id` for a model named User
        if (!isset($property['foreign_key'])) {
            $inflector = Inflector::get();
            $property['foreign_key'] = strtolower($inflector->underscore($modelClass::modelName())).'_id';
        }

        // the default local key is `id`
        if (!isset($property['local_key'])) {
            $property['local_key'] = Model::DEFAULT_ID_NAME;
        }
    }

    private static function buildPolymorphic(array &$property, string $name): void
    {
        /* @var Model $modelClass */
        $property['relation_type'] = Relationship::POLYMORPHIC;
        $property['persisted'] = false;
        $property['in_array'] = false;

        // the default foreign key is `id`
        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }

        // the default local key type is the property name
        if (!isset($property['local_key'])) {
            $property['local_key'] = $name;
        }

        // when a polymorhpic relationship is used then we automatically add a
        // new property for the type and ID fields which gets persisted to the DB
        if (!isset($result[$property['local_key'].'_type'])) {
            $result[$property['local_key'].'_type'] = new Property([
                'type' => Type::STRING,
                'mutable' => $property['mutable'] ?? Property::MUTABLE,
                'name' => $property['local_key'].'_type',
            ]);
        }

        if (!isset($result[$property['local_key'].'_id'])) {
            $result[$property['local_key'].'_id'] = new Property([
                'type' => Type::INTEGER,
                'mutable' => $property['mutable'] ?? Property::MUTABLE,
                'name' => $property['local_key'].'_id',
            ]);
        }
    }
}
