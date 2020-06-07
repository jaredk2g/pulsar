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

use ICanBoogie\Inflector;
use Pulsar\Model;
use Pulsar\Query;

/**
 * Represents a has-one relationship.
 */
class HasOne extends Relation
{
    /**
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, ?string $localKey, string $foreignModel, ?string $foreignKey)
    {
        // the default foreign key would look like
        // `user_id` for a model named User
        if (!$foreignKey) {
            $inflector = Inflector::get();
            $foreignKey = strtolower($inflector->underscore($localModel::modelName())).'_id';
        }

        if (!$localKey) {
            $localKey = Model::DEFAULT_ID_PROPERTY;
        }

        parent::__construct($localModel, $localKey, $foreignModel, $foreignKey);
    }

    protected function initQuery(Query $query)
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
            return;
        }

        return $query->first();
    }

    public function save(Model $model)
    {
        $model->{$this->foreignKey} = $this->localModel->{$this->localKey};
        $model->saveOrFail();

        return $model;
    }

    public function create(array $values = [])
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
     * @throws \Pulsar\Exception\ModelException when the operation fails
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
     * @throws \Pulsar\Exception\ModelException when the operation fails
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
