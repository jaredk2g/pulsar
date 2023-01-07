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

use Pulsar\Exception\DriverException;
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
     * @throws DriverException when an exception occurs within the driver
     */
    public function createModel(Model $model, array $parameters): bool;

    /**
     * Gets the last inserted ID. Used for drivers that generate
     * IDs for models after creation.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function getCreatedId(Model $model, string $propertyName): mixed;

    /**
     * Loads a model.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function loadModel(Model $model): ?array;

    /**
     * Performs a query to find models of the given type.
     *
     * @throws DriverException when an exception occurs within the driver
     *
     * @return array raw data from storage
     */
    public function queryModels(Query $query): array;

    /**
     * Updates a model.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function updateModel(Model $model, array $parameters): bool;

    /**
     * Deletes a model.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function deleteModel(Model $model): bool;

    /**
     * Gets the count matching the given query.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function count(Query $query): int;

    /**
     * Gets the sum matching the given query.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function sum(Query $query, string $field): float;

    /**
     * Gets the average matching the given query.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function average(Query $query, string $field): float;

    /**
     * Gets the max matching the given query.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function max(Query $query, string $field): float;

    /**
     * Gets the min matching the given query.
     *
     * @throws DriverException when an exception occurs within the driver
     */
    public function min(Query $query, string $field): float;

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
