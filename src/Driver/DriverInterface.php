<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Driver;

use Pulsar\Model;
use Pulsar\Query;

/**
 * Interface DriverInterface.
 */
interface DriverInterface
{
    /**
     * Creates a model.
     *
     * @param Model $model
     * @param array $parameters
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
     * @param Model  $model
     * @param string $propertyName
     *
     * @return mixed
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function getCreatedID(Model $model, $propertyName);

    /**
     * Loads a model.
     *
     * @param Model $model
     *
     * @return array|false
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function loadModel(Model $model);

    /**
     * Updates a model.
     *
     * @param Model $model
     * @param array $parameters
     *
     * @return bool
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function updateModel(Model $model, array $parameters);

    /**
     * Deletes a model.
     *
     * @param Model $model
     *
     * @return bool
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function deleteModel(Model $model);

    /**
     * Gets the count matching the given query.
     *
     * @param Query $query
     *
     * @return int
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function count(Query $query);

    /**
     * Gets the sum matching the given query.
     *
     * @param Query  $query
     * @param string $field
     *
     * @return number
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function sum(Query $query, $field);

    /**
     * Gets the average matching the given query.
     *
     * @param Query  $query
     * @param string $field
     *
     * @return number
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function average(Query $query, $field);

    /**
     * Gets the max matching the given query.
     *
     * @param Query  $query
     * @param string $field
     *
     * @return number
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function max(Query $query, $field);

    /**
     * Gets the min matching the given query.
     *
     * @param Query  $query
     * @param string $field
     *
     * @return number
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function min(Query $query, $field);

    /**
     * Performs a query to find models of the given type.
     *
     * @param Query $query
     *
     * @return array raw data from storage
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function queryModels(Query $query);
}
