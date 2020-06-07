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
     * @return mixed result
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function createModel(Model $model, array $parameters);

    /**
     * Gets the last inserted ID. Used for drivers that generate
     * IDs for models after creation.
     *
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
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function loadModel(Model $model): ?array;

    /**
     * Performs a query to find models of the given type.
     *
     * @return array raw data from storage
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function queryModels(Query $query): array;

    /**
     * Updates a model.
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function updateModel(Model $model, array $parameters): bool;

    /**
     * Deletes a model.
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function deleteModel(Model $model): bool;

    /**
     * Gets the count matching the given query.
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function count(Query $query): int;

    /**
     * Gets the sum matching the given query.
     *
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
     * @param string $field
     *
     * @return number
     *
     * @throws \Pulsar\Exception\DriverException when an exception occurs within the driver
     */
    public function min(Query $query, $field);

    /**
     * Starts a database transaction.
     */
    public function startTransaction(?string $connection): void;

    /**
     * Rolls back the open database transaction.
     */
    public function rollBackTransaction(?string $connection): void;

    /**
     * Commits the database transaction.
     */
    public function commitTransaction(?string $connection): void;
}
