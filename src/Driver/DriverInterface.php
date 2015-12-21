<?php

/**
 * @package Pulsar
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Driver;

use Pulsar\Model;
use Pulsar\Query;

interface DriverInterface
{
    /**
     * Creates a model.
     *
     * @param \Pulsar\Model $model
     * @param array         $parameters
     *
     * @return mixed result
     */
    public function createModel(Model $model, array $parameters);

    /**
     * Gets the last inserted ID. Used for drivers that generate
     * IDs for models after creation.
     *
     * @param \Pulsar\Model $model
     * @param string        $propertyName
     *
     * @return mixed
     */
    public function getCreatedID(Model $model, $propertyName);

    /**
     * Loads a model.
     *
     * @param \Pulsar\Model $model
     *
     * @return array
     */
    public function loadModel(Model $model);

    /**
     * Updates a model.
     *
     * @param \Pulsar\Model $model
     * @param array         $parameters
     *
     * @return bool
     */
    public function updateModel(Model $model, array $parameters);

    /**
     * Deletes a model.
     *
     * @param \Pulsar\Model $model
     *
     * @return bool
     */
    public function deleteModel(Model $model);

    /**
     * Gets the toal number of records matching the given query.
     *
     * @param \Pulsar\Query $query
     *
     * @return int total
     */
    public function totalRecords(Query $query);

    /**
     * Performs a query to find models of the given type.
     *
     * @param \Pulsar\Query $query
     *
     * @return array raw data from storage
     */
    public function queryModels(Query $query);
}
