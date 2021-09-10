<?php

namespace Pulsar;

use Pulsar\Relation\Relationship;

class Hydrator
{
    /**
     * @property string|Model $modelClass
     *
     * @return Model[]
     */
    public static function hydrate(array $result, $modelClass, array $eagerLoaded): array
    {
        $ids = [];
        $eagerLoadedProperties = [];
        foreach ($eagerLoaded as $k) {
            $eagerLoadedProperties[$k] = $modelClass::definition()->get($k);
            $ids[$k] = [];
        }

        // fetch the models matching the query
        /** @var Model[] $models */
        $models = [];
        foreach ($result as $j => $row) {
            // type-cast the values because they came from the database
            foreach ($row as $k => &$v) {
                if ($property = $modelClass::definition()->get($k)) {
                    $v = Type::cast($property, $v);
                }
            }

            // create the model and cache the loaded values
            $models[] = new $modelClass($row);

            // capture any local ids for eager loading relationships
            foreach ($eagerLoaded as $k) {
                $localKey = $eagerLoadedProperties[$k]['local_key'];
                if (isset($row[$localKey])) {
                    $ids[$k][$j] = $row[$localKey];
                }
            }
        }

        // hydrate the eager loaded relationships
        foreach ($eagerLoaded as $k) {
            $property = $eagerLoadedProperties[$k];
            $relationModelClass = $property->getForeignModelClass();
            $type = $property->getRelationshipType();

            if (Relationship::BELONGS_TO == $type) {
                $relationships = self::fetchRelationships($relationModelClass, $ids[$k], $property->getForeignKey(), false);

                foreach ($ids[$k] as $j => $id) {
                    if (isset($relationships[$id])) {
                        if ($property->isPersisted()) {
                            $models[$j]->setRelation($k, $relationships[$id]);
                        // older style properties do not support this type of hydration
                        } else {
                            $models[$j]->hydrateValue($k, $relationships[$id]);
                        }
                    }
                }
            } elseif (Relationship::HAS_ONE == $type) {
                $relationships = self::fetchRelationships($relationModelClass, $ids[$k], $property->getForeignKey(), false);

                foreach ($ids[$k] as $j => $id) {
                    if (isset($relationships[$id])) {
                        if ($property->isPersisted()) {
                            $models[$j]->setRelation($k, $relationships[$id]);
                        // older style properties do not support this type of hydration
                        } else {
                            $models[$j]->hydrateValue($k, $relationships[$id]);
                        }
                    } else {
                        // when using has one eager loading we must
                        // explicitly mark the relationship as null
                        // for models not found during eager loading
                        // or else it will trigger another DB call
                        $models[$j]->clearRelation($k);

                        // older style properties do not support this type of hydration
                        if (!$property->isPersisted()) {
                            $models[$j]->hydrateValue($k, null);
                        }
                    }
                }
            } elseif (Relationship::HAS_MANY == $type) {
                $relationships = self::fetchRelationships($relationModelClass, $ids[$k], $property->getForeignKey(), true);

                foreach ($ids[$k] as $j => $id) {
                    if (isset($relationships[$id])) {
                        $models[$j]->setRelationCollection($k, $relationships[$id]);
                        // older style properties do not support this type of hydration
                        if (!$property->isPersisted()) {
                            $models[$j]->hydrateValue($k, $relationships[$id]);
                        }
                    } else {
                        $models[$j]->setRelationCollection($k, []);
                        // older style properties do not support this type of hydration
                        if (!$property->isPersisted()) {
                            $models[$j]->hydrateValue($k, []);
                        }
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Hydrates the eager-loaded relationships for a given set of IDs.
     *
     * @param string $modelClass
     * @param bool   $multiple   when true will condense
     *
     * @return Model[]
     */
    private static function fetchRelationships($modelClass, array $ids, string $foreignKey, bool $multiple): array
    {
        $uniqueIds = array_unique($ids);
        if (0 === count($uniqueIds)) {
            return [];
        }

        $in = $foreignKey.' IN ('.implode(',', $uniqueIds).')';
        $models = $modelClass::where($in)
            ->first(Query::MAX_LIMIT);

        $result = [];
        foreach ($models as $model) {
            if ($multiple) {
                if (!isset($result[$model->$foreignKey])) {
                    $result[$model->$foreignKey] = [];
                }
                $result[$model->$foreignKey][] = $model;
            } else {
                $result[$model->$foreignKey] = $model;
            }
        }

        return $result;
    }
}
