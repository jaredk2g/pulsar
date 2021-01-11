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

use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\Relationship;

/**
 * Represents a query against a model type.
 */
class Query
{
    const DEFAULT_LIMIT = 100;
    const MAX_LIMIT = 1000;

    /**
     * @var Model|string
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
     * @param Model|string $model
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
     * @return Model|string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the limit for this query.
     *
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->limit = min($limit, self::MAX_LIMIT);

        return $this;
    }

    /**
     * Gets the limit for this query.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Sets the start offset.
     *
     * @return $this
     */
    public function start(int $start)
    {
        $this->start = max($start, 0);

        return $this;
    }

    /**
     * Gets the start offset.
     */
    public function getStart(): int
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
     */
    public function getSort(): array
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
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Adds a join to the query. Matches a property on this model
     * to the ID of the model we are joining.
     *
     * @param string $model  model being joined
     * @param string $column name of local property
     *
     * @return $this
     */
    public function join($model, string $column, string $foreignKey)
    {
        $this->joins[] = [$model, $column, $foreignKey];

        return $this;
    }

    /**
     * Gets the joins.
     */
    public function getJoins(): array
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
    public function with(string $k)
    {
        if (!in_array($k, $this->eagerLoaded)) {
            $this->eagerLoaded[] = $k;
        }

        return $this;
    }

    /**
     * Gets the relationship properties that are going to be eager-loaded.
     */
    public function getWith(): array
    {
        return $this->eagerLoaded;
    }

    /**
     * Executes the query against the model's driver.
     *
     * @return Model[] results
     */
    public function execute(): array
    {
        // instantiate a model so that initialize() is called and properties are filled in
        // otherwise this empty model is not used
        $modelClass = $this->model;
        $model = new $modelClass();

        return Hydrator::hydrate($modelClass::getDriver()->queryModels($this), $modelClass, $this->eagerLoaded);
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
     * Finds exactly one model. If zero or more than one are found
     * then this function will fail.
     *
     * @throws ModelNotFoundException when the result is not exactly one model
     */
    public function one(): Model
    {
        $models = $this->limit(1)->execute();

        if (0 == count($models)) {
            $model = $this->model;
            throw new ModelNotFoundException('Could not find '.$model::modelName());
        } elseif (count($models) > 1) {
            $model = $this->model;
            throw new ModelNotFoundException('Found more than one '.$model::modelName().' when only one result should have been found.');
        }

        return $models[0];
    }

    /**
     * Executes the query against the model's driver and returns the first result.
     *
     * @return Model[]|Model|null when $limit = 1, returns a single model or null, otherwise returns an array
     */
    public function first(int $limit = 1)
    {
        $models = $this->limit($limit)->execute();

        if (1 == $limit) {
            return (1 == count($models)) ? $models[0] : null;
        }

        return $models;
    }

    /**
     * Gets the number of models matching the query.
     */
    public function count(): int
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->count($this);
    }

    /**
     * Gets the sum of a property matching the query.
     *
     * @return number
     */
    public function sum(string $property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->sum($this, $property);
    }

    /**
     * Gets the average of a property matching the query.
     *
     * @return number
     */
    public function average(string $property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->average($this, $property);
    }

    /**
     * Gets the max of a property matching the query.
     *
     * @return number
     */
    public function max(string $property)
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->max($this, $property);
    }

    /**
     * Gets the min of a property matching the query.
     *
     * @return number
     */
    public function min(string $property)
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
    public function set(array $params): int
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
    public function delete(): int
    {
        $n = 0;
        foreach ($this->all() as $model) {
            $model->delete();
            ++$n;
        }

        return $n;
    }
}
