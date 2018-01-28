<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

/**
 * Represents a query against a model type.
 */
class Query
{
    const DEFAULT_LIMIT = 100;
    const MAX_LIMIT = 1000;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array
     */
    private $joins;

    /**
     * @var array
     */
    private $eagerLoaded;

    /**
     * @var array
     */
    private $where;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $start;

    /**
     * @var array
     */
    private $sort;

    /**
     * @param string $model model class
     */
    public function __construct($model = '')
    {
        $this->model = $model;
        $this->joins = [];
        $this->eagerLoaded = [];
        $this->where = [];
        $this->start = 0;
        $this->limit = self::DEFAULT_LIMIT;
        $this->sort = [];
    }

    /**
     * Gets the model class associated with this query.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the limit for this query.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = min($limit, self::MAX_LIMIT);

        return $this;
    }

    /**
     * Gets the limit for this query.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Sets the start offset.
     *
     * @param int $start
     *
     * @return $this
     */
    public function start($start)
    {
        $this->start = max($start, 0);

        return $this;
    }

    /**
     * Gets the start offset.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets the sort pattern for the query.
     *
     * @param array|string $sort
     *
     * @return $this
     */
    public function sort($sort)
    {
        $columns = explode(',', $sort);

        $sortParams = [];
        foreach ($columns as $column) {
            $c = explode(' ', trim($column));

            if (2 != count($c)) {
                continue;
            }

            // validate direction
            $direction = strtolower($c[1]);
            if (!in_array($direction, ['asc', 'desc'])) {
                continue;
            }

            $sortParams[] = [$c[0], $direction];
        }

        $this->sort = $sortParams;

        return $this;
    }

    /**
     * Gets the sort parameters.
     *
     * @return array
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Sets the where parameters.
     * Accepts the following forms:
     *   i)   where(['name' => 'Bob'])
     *   ii)  where('name', 'Bob')
     *   iii) where('balance', 100, '>')
     *   iv)  where('balance > 100').
     *
     * @param array|string $where
     * @param mixed        $value     optional value
     * @param string|null  $condition optional condition
     *
     * @return $this
     */
    public function where($where, $value = null, $condition = null)
    {
        // handles i.
        if (is_array($where)) {
            $this->where = array_merge($this->where, $where);
        } else {
            // handles iii.
            $args = func_num_args();
            if ($args > 2) {
                $this->where[] = [$where, $value, $condition];
            // handles ii.
            } elseif (2 == $args) {
                $this->where[$where] = $value;
            // handles iv.
            } else {
                $this->where[] = $where;
            }
        }

        return $this;
    }

    /**
     * Gets the where parameters.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Adds a join to the query. Matches a property on this model
     * to the ID of the model we are joining.
     *
     * @param string $model      model being joined
     * @param string $column     name of local property
     * @param string $foreignKey
     *
     * @return $this
     */
    public function join($model, $column, $foreignKey)
    {
        $this->joins[] = [$model, $column, $foreignKey];

        return $this;
    }

    /**
     * Gets the joins.
     *
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Marks a relationship property on the model that should be eager loaded.
     *
     * @param string $k local property containing the relationship
     *
     * @return $this
     */
    public function with($k)
    {
        if (!in_array($k, $this->eagerLoaded)) {
            $this->eagerLoaded[] = $k;
        }

        return $this;
    }

    /**
     * Gets the relationship properties that are going to be eager-loaded.
     *
     * @return array
     */
    public function getWith()
    {
        return $this->eagerLoaded;
    }

    /**
     * Executes the query against the model's driver.
     *
     * @return array results
     */
    public function execute()
    {
        $models = [];
        $model = $this->model;

        $ids = [];
        $eagerLoadedProperties = [];
        foreach ($this->eagerLoaded as $k) {
            $ids[$k] = [];
            $eagerLoadedProperties[$k] = $model::getProperty($k);
        }

        // fetch the models matching the query
        $driver = $model::getDriver();
        foreach ($driver->queryModels($this) as $row) {
            // get the model's ID
            $id = [];
            foreach ($model::getIDProperties() as $k) {
                $id[] = $row[$k];
            }

            // create the model and cache the loaded values
            $models[] = new $model($id, $row);
            foreach ($this->eagerLoaded as $k) {
                $property = $eagerLoadedProperties[$k];
                $localKey = $property['local_key'];
                if ($row[$localKey]) {
                    $ids[$k][] = $row[$localKey];
                }
            }
        }

        // hydrate the eager loaded relationships
        foreach ($this->eagerLoaded as $k) {
            $property = $eagerLoadedProperties[$k];
            $relationModelClass = $property['relation'];

            if (Model::RELATIONSHIP_BELONGS_TO == $property['relation_type']) {
                $relationships = $this->fetchRelationships($relationModelClass, $ids[$k], $property['foreign_key']);

                foreach ($ids[$k] as $j => $id) {
                    if (isset($relationships[$id])) {
                        $models[$j]->setRelation($k, $relationships[$id]);
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Creates an iterator for a search.
     *
     * @return Iterator
     */
    public function all()
    {
        return new Iterator($this);
    }

    /**
     * Executes the query against the model's driver and returns the first result.
     *
     * @param int $limit
     *
     * @return array|Model|null when $limit = 1, returns a single model or null, otherwise returns an array
     */
    public function first($limit = 1)
    {
        $models = $this->limit($limit)->execute();

        if (1 == $limit) {
            return (1 == count($models)) ? $models[0] : null;
        }

        return $models;
    }

    /**
     * Gets the number of models matching the query.
     *
     * @return int
     */
    public function count()
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->count($this);
    }

    /**
     * @deprecated
     *
     * Gets the total number of records matching an optional criteria
     *
     * @param array $where criteria
     *
     * @return int
     */
    public function totalRecords(array $where = [])
    {
        return $this->where($where)->count();
    }

    /**
     * Gets the sum of a property matching the query.
     *
     * @param string $property
     *
     * @return int
     */
    public function sum($property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->sum($this, $property);
    }

    /**
     * Gets the average of a property matching the query.
     *
     * @param string $property
     *
     * @return int
     */
    public function average($property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->average($this, $property);
    }

    /**
     * Gets the max of a property matching the query.
     *
     * @param string $property
     *
     * @return int
     */
    public function max($property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->max($this, $property);
    }

    /**
     * Gets the min of a property matching the query.
     *
     * @param string $property
     *
     * @return int
     */
    public function min($property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->min($this, $property);
    }

    /**
     * Updates all of the models matched by this query.
     *
     * @todo should be optimized to be done in a single call to the data layer
     *
     * @param array $params key-value update parameters
     *
     * @return int # of models updated
     */
    public function set(array $params)
    {
        $n = 0;
        foreach ($this->all() as $model) {
            $model->set($params);
            ++$n;
        }

        return $n;
    }

    /**
     * Deletes all of the models matched by this query.
     *
     * @todo should be optimized to be done in a single call to the data layer
     *
     * @return int # of models deleted
     */
    public function delete()
    {
        $n = 0;
        foreach ($this->all() as $model) {
            $model->delete();
            ++$n;
        }

        return $n;
    }

    /**
     * Hydrates the eager-loaded relationships for a given set of models.
     *
     * @param string $modelClass
     * @param array  $ids
     * @param string $foreignKey
     *
     * @return array
     */
    private function fetchRelationships($modelClass, array $ids, $foreignKey = 'id')
    {
        $uniqueIds = array_unique($ids);
        if (0 === count($uniqueIds)) {
            return [];
        }

        $in = $foreignKey.' IN ('.implode(',', $uniqueIds).')';
        $models = $modelClass::where($in)
                             ->first(self::MAX_LIMIT);

        $result = [];
        foreach ($models as $model) {
            $result[$model->id()] = $model;
        }

        return $result;
    }
}
