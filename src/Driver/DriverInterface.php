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
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
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
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function getCreatedID(Model $model, $propertyName);

    /**
     * Loads a model.
     *
     * @param \Pulsar\Model $model
     *
     * @return array|false
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function loadModel(Model $model);

    /**
     * Updates a model.
     *
     * @param \Pulsar\Model $model
     * @param array         $parameters
     *
     * @return bool
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function updateModel(Model $model, array $parameters);

    /**
     * Deletes a model.
     *
     * @param \Pulsar\Model $model
     *
     * @return bool
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function deleteModel(Model $model);

    /**
     * Gets the toal number of records matching the given query.
     *
     * @param \Pulsar\Query $query
     *
     * @return int total
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function totalRecords(Query $query);

    /**
     * Performs a query to find models of the given type.
     *
     * @param \Pulsar\Query $query
     *
     * @return array raw data from storage
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function queryModels(Query $query);
}
