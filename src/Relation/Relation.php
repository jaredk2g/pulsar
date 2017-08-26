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
 * Base relationship class.
 */
abstract class Relation
{
    /**
     * @var \Pulsar\Model
     */
    protected $localModel;

    /**
     * @var string
     */
    protected $localKey;

    /**
     * @var string
     */
    protected $foreignModel;

    /**
     * @var string
     */
    protected $foreignKey;

    /**
     * @var \Pulsar\Query
     */
    protected $query;

    /**
     * @var bool
     */
    protected $empty;

    /**
     * @param Model  $localModel
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, $localKey, $foreignModel, $foreignKey)
    {
        $this->localModel = $localModel;
        $this->localKey = $localKey;

        $this->foreignModel = $foreignModel;
        $this->foreignKey = $foreignKey;

        $this->query = new Query(new $foreignModel());
        $this->initQuery();
    }

    /**
     * Gets the local model of the relationship.
     *
     * @return \Pulsar\Model
     */
    public function getLocalModel()
    {
        return $this->localModel;
    }

    /**
     * Gets the name of the foreign key of the relation model.
     *
     * @return string
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * Gets the foreign model of the relationship.
     *
     * @return string
     */
    public function getForeignModel()
    {
        return $this->foreignModel;
    }

    /**
     * Gets the name of the foreign key of the foreign model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Returns the query instance for this relation.
     *
     * @return \Pulsar\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Called to initialize the query.
     */
    abstract protected function initQuery();

    /**
     * Called to get the results of the relation query.
     *
     * @return mixed
     */
    abstract public function getResults();

    /**
     * Saves a new relationship model and attaches it to
     * the owning model.
     *
     * @param Model $model
     *
     * @throws ModelException when the operation fails
     *
     * @return Model
     */
    abstract public function save(Model $model);

    /**
     * Creates a new relationship model and attaches it to
     * the owning model.
     *
     * @param array $values
     *
     * @throws ModelException when the operation fails
     *
     * @return Model
     */
    abstract public function create(array $values = []);

    public function __call($method, $arguments)
    {
        // try calling any unkown methods on the query
        return call_user_func_array([$this->query, $method], $arguments);
    }
}
