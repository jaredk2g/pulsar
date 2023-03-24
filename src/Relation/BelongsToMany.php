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
use function _PHPStan_9dde7d81b\React\Promise\Stream\first;

/**
 * Represents a belongs-to-many relationship.
 */
final class BelongsToMany extends AbstractRelation
{
    protected string $tablename;

    /**
     * @param string $localKey     identifying key on local model
     * @param string $tablename    pivot table name
     * @param string $foreignModel foreign model class
     * @param string $foreignKey   identifying key on foreign model
     */
    public function __construct(Model $localModel, string $localKey, string $tablename, string $foreignModel, string $foreignKey)
    {
        $this->tablename = $tablename;

        parent::__construct($localModel, $localKey, $foreignModel, $foreignKey);
    }

    protected function initQuery(Query $query): Query
    {
        $pivot = new Pivot();
        $pivot->setTablename($this->tablename);

        $ids = $this->localModel->ids();
        $foreignIds = $query->getModel()->ids();
        // known issue - this will work only on single join  column
        if (count($foreignIds) !== 1 && count($ids) != 1) {
            $this->empty = true;
            return $query;
        }
        $id = array_shift($ids);
        if (!$id) {
            $this->empty = true;
            return $query;
        }
        $firstForeignKey = array_key_first($foreignIds);

        $query->where("$this->tablename.$this->localKey = $id");
        $query->join($pivot, $this->foreignKey, $firstForeignKey);

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

    /**
     * Gets the pivot tablename.
     */
    public function getTablename(): string
    {
        return $this->tablename;
    }

    public function save(Model $model): Model
    {
        $model->saveOrFail();
        $this->attach($model);

        return $model;
    }

    public function create(array $values = []): Model
    {
        $class = $this->foreignModel;
        $model = new $class();
        $model->create($values);

        $this->attach($model);

        return $model;
    }

    /**
     * Attaches a model to the relationship by creating
     * a pivot model.
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function attach(Model $model): self
    {
        // create pivot relation
        $pivot = new Pivot();
        $pivot->setTablename($this->tablename);
        $pivot->setProperties($this->localKey, $this->foreignKey);

        // build the local side
        $ids = $this->localModel->ids();
        foreach ($ids as $id) {
            $pivot->{$this->localKey} = $id;
        }

        // build the foreign side
        $ids = $model->ids();
        foreach ($ids as $id) {
            $pivot->{$this->foreignKey} = $id;
        }

        $pivot->saveOrFail();
        $model->pivot = $pivot;

        return $this;
    }

    /**
     * Detaches a model from the relationship by deleting
     * the pivot model.
     *
     * @throws ModelException when the operation fails
     *
     * @return $this
     */
    public function detach(Model $model): self
    {
        $model->pivot->delete();
        unset($model->pivot);

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
        $pivot = new Pivot();
        $pivot->setTablename($this->tablename);
        $query = new Query($pivot);

        $localIds = $this->localModel->ids();
        foreach ($localIds as $property => $id) {
            $query->where($this->localKey, $id);
        }

        if (count($ids) > 0) {
            $in = implode(',', $ids);
            $query->where("{$this->foreignKey} NOT IN ($in)");
        }

        $query->delete();

        return $this;
    }
}
