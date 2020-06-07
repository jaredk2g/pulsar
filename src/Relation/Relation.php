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
     * @var Model
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
     * @var bool
     */
    protected $empty;

    /**
     * @param string $localKey     identifying key on local model
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, string $localKey, string $foreignModel, string $foreignKey)
    {
        $this->localModel = $localModel;
        $this->localKey = $localKey;

        $this->foreignModel = $foreignModel;
        $this->foreignKey = $foreignKey;
    }

    /**
     * Gets the local model of the relationship.
     */
    public function getLocalModel(): Model
    {
        return $this->localModel;
    }

    /**
     * Gets the name of the foreign key of the relation model.
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Gets the foreign model of the relationship.
     */
    public function getForeignModel(): string
    {
        return $this->foreignModel;
    }

    /**
     * Gets the name of the foreign key of the foreign model.
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Returns the query instance for this relation.
     */
    public function getQuery(): Query
    {
        $foreignModel = $this->foreignModel;
        $query = new Query(new $foreignModel());
        $this->initQuery($query);

        return $query;
    }

    /**
     * Called to initialize the query.
     */
    abstract protected function initQuery(Query $query): Query;

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
     * @throws ModelException when the operation fails
     */
    abstract public function save(Model $model): Model;

    /**
     * Creates a new relationship model and attaches it to
     * the owning model.
     *
     * @throws ModelException when the operation fails
     */
    abstract public function create(array $values = []): Model;

    public function __call($method, $arguments)
    {
        // try calling any unknown methods on the query
        return call_user_func_array([$this->getQuery(), $method], $arguments);
    }
}
