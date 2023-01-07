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
 * Represents a has-many relationship.
 */
final class HasMany extends AbstractRelation
{
    /**
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, ?string $localKey, string $foreignModel, ?string $foreignKey)
    {
        parent::__construct($localModel, $localKey, $foreignModel, $foreignKey);
    }

    protected function initQuery(Query $query): Query
    {
        $localKey = $this->localKey;
        $id = $this->localModel->$localKey;

        if (null === $id) {
            $this->empty = true;
        }

        $query->where($this->foreignKey, $id);

        return $query;
    }

    /**
     * @return Model[]|null
     */
    public function getResults(): ?array
    {
        $query = $this->getQuery();
        if ($this->empty) {
            return null;
        }

        return $query->execute();
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
     * Attaches a child model from this model.
     *
     * @param Model $model child model
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function attach(Model $model): self
    {
        $model->{$this->foreignKey} = $this->localModel->{$this->localKey};
        $model->saveOrFail();

        return $this;
    }

    /**
     * Detaches a child model from this model.
     *
     * @param Model $model child model
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function detach(Model $model): self
    {
        $model->{$this->foreignKey} = null;
        $model->saveOrFail();

        return $this;
    }

    /**
     * Removes any relationships that are not included
     * in the list of IDs.
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function sync(array $ids): self
    {
        $class = $this->foreignModel;
        $model = new $class();
        $query = $model::query();

        if (count($ids) > 0) {
            $in = implode(',', $ids);
            $query->where("{$this->foreignKey} NOT IN ($in)");
        }

        $query->delete();

        return $this;
    }
}
