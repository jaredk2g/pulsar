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

use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\Relationship;

/**
 * Represents a query against a model type.
 *
 * @template T
 */
class Query
{
    const DEFAULT_LIMIT = 100;
    const MAX_LIMIT = 1000;

    private Model|string $model;
    private array $joins = [];
    private array $eagerLoaded = [];
    private array $where = [];
    private int $limit = self::DEFAULT_LIMIT;
    private int $start = 0;
    private array $sort = [];

    public function __construct(Model|string $model = '')
    {
        $this->model = $model;
    }

    /**
     * Gets the model class associated with this query.
     */
    public function getModel(): Model|string
    {
        return $this->model;
    }

    /**
     * Sets the limit for this query.
     *
     * @return $this
     */
    public function limit(int $limit): self
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
    public function start(int $start): self
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
     * @return $this
     */
    public function sort(array|string $sort): self
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
     * @param mixed       $value     optional value
     * @param string|null $condition optional condition
     *
     * @return $this
     */
    public function where(array|string $where, mixed $value = null, string|null $condition = null): self
    {
        // handles i.
        if (is_array($where)) {
            // only key-value format is accepted in arrays
            foreach ($where as $key => $value) {
                $this->where($key, $value);
            }
        } else {
            if ($value instanceof Model) {
                $value = $value->id();
            }

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
     * @param Model|string $model  model being joined
     * @param string       $column name of local property
     *
     * @return $this
     */
    public function join(Model|string $model, string $column, string $foreignKey, string $type = 'JOIN'): self
    {
        $join = [$model, $column, $foreignKey, $type];
        // Ensure there are no duplicate joins
        foreach ($this->joins as $join2) {
            if ($join == $join2) {
                return $this;
            }
        }
        $this->joins[] = $join;

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
    public function with(string $k): self
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
     * @return T[] results
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
     * @return Iterator<T>
     */
    public function all(): mixed
    {
        return new Iterator($this);
    }

    /**
     * Finds exactly one model. If zero or more than one are found
     * then this function will fail.
     *
     * @throws ModelNotFoundException when the result is not exactly one model
     *
     * @return T
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
     * Finds exactly one model. If zero or more than one are found
     * then this function will return null.
     *
     * @throws ModelNotFoundException when the result is not exactly one model
     *
     * @return T|null
     */
    public function oneOrNull(): ?Model
    {
        $models = $this->limit(1)->execute();

        return 1 == count($models) ? $models[0] : null;
    }

    /**
     * Executes the query against the model's driver and returns the first result.
     *
     * @return T[]
     */
    public function first(int $limit = 1): array
    {
        return $this->limit($limit)->execute();
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
     */
    public function sum(string $property): float
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->sum($this, $property);
    }

    /**
     * Gets the average of a property matching the query.
     */
    public function average(string $property): float
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->average($this, $property);
    }

    /**
     * Gets the max of a property matching the query.
     */
    public function max(string $property): float
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->max($this, $property);
    }

    /**
     * Gets the min of a property matching the query.
     */
    public function min(string $property): float
    {
        $model = $this->model;
        $driver = $model::getDriver();

        return $driver->min($this, $property);
    }

    /**
     * Updates all the models matched by this query.
     *
     * @todo should be optimized to be done in a single call to the data layer
     *
     * @param array $params key-value update parameters
     *
     * @throws ModelException
     *
     * @return int # of models updated
     */
    public function set(array $params): int
    {
        $n = 0;
        /** @var Model $model */
        foreach ($this->all() as $model) {
            if (!$model->set($params)) {
                throw new ModelException('Could not modify '.$model::modelName().': '.$model->getErrors());
            }
            ++$n;
        }

        return $n;
    }

    /**
     * Deletes all the models matched by this query.
     *
     * @todo should be optimized to be done in a single call to the data layer
     *
     * @throws ModelException
     *
     * @return int # of models deleted
     */
    public function delete(): int
    {
        $n = 0;
        /** @var Model $model */
        foreach ($this->all() as $model) {
            $model->deleteOrFail();
            ++$n;
        }

        return $n;
    }
}
