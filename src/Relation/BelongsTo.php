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
 * Represents a belongs-to relationship.
 */
class BelongsTo extends Relation
{
    /**
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, ?string $localKey, string $foreignModel, ?string $foreignKey)
    {
        if (!$foreignKey) {
            $foreignKey = Model::DEFAULT_ID_NAME;
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($foreignModel::modelName())).'_id';
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
        $model->saveOrFail();
        $this->attach($model);

        return $model;
    }

    public function create(array $values = [])
    {
        $class = $this->foreignModel;
        $model = new $class();
        $model->create($values);

        $this->attach($model);

        return $model;
    }

    /**
     * Attaches this model to an owning model.
     *
     * @param Model $model owning model
     *
     * @return $this
     */
    public function attach(Model $model)
    {
        $this->localModel->{$this->localKey} = $model->{$this->foreignKey};
        $this->localModel->saveOrFail();

        return $this;
    }

    /**
     * Detaches this model from the owning model.
     *
     * @return $this
     */
    public function detach()
    {
        $this->localModel->{$this->localKey} = null;
        $this->localModel->saveOrFail();

        return $this;
    }
}
