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

class BelongsTo extends Relation
{
    /**
     * @param Model  $localModel
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, $localKey, $foreignModel, $foreignKey)
    {
        if (!$foreignKey) {
            $foreignKey = Model::DEFAULT_ID_PROPERTY;
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($foreignModel::modelName())).'_id';
        }

        parent::__construct($localModel, $localKey, $foreignModel, $foreignKey);
    }

    protected function initQuery()
    {
        $id = $this->localModel->{$this->localKey};

        if ($id === null) {
            $this->empty = true;
        }

        $this->query->where($this->foreignKey, $id)
            ->limit(1);
    }

    public function getResults()
    {
        if ($this->empty) {
            return;
        }

        return $this->query->first();
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
     * @return self
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
     * @return self
     */
    public function detach()
    {
        $this->localModel->{$this->localKey} = null;
        $this->localModel->saveOrFail();

        return $this;
    }
}
