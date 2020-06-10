<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Relation;

use Pulsar\Exception\ModelException;
use Pulsar\Model;
use Pulsar\Query;

/**
 * Represents a has-one relationship.
 */
final class HasOne extends AbstractRelation
{
    protected function initQuery(Query $query): Query
    {
        $id = $this->localModel->{$this->localKey};

        if (null === $id) {
            $this->empty = true;
        }

        $query->where($this->foreignKey, $id)
            ->limit(1);

        return $query;
    }

    public function getResults()
    {
        $query = $this->getQuery();
        if ($this->empty) {
            return null;
        }

        return $query->first();
    }

    public function save(Model $model): Model
    {
        $model->{$this->foreignKey} = $this->localModel->{$this->localKey};
        $model->saveOrFail();

        return $model;
    }

    public function create(array $values = []): Model
    {
        $class = $this->foreignModel;
        $model = new $class();
        $model->{$this->foreignKey} = $this->localModel->{$this->localKey};
        $model->create($values);

        return $model;
    }

    /**
     * Attaches a child model to this model.
     *
     * @param Model $model child model
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function attach(Model $model)
    {
        $model->{$this->foreignKey} = $this->localModel->{$this->localKey};
        $model->saveOrFail();

        return $this;
    }

    /**
     * Detaches the child model from this model.
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function detach()
    {
        $model = $this->getResults();

        if ($model) {
            $model->{$this->foreignKey} = null;
            $model->saveOrFail();
        }

        return $this;
    }
}
