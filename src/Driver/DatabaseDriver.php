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

use JAQB\ConnectionManager;
use JAQB\Exception\JAQBException;
use JAQB\Query\SelectQuery;
use JAQB\QueryBuilder;
use PDOException;
use PDOStatement;
use Pulsar\Exception\DriverException;
use Pulsar\Model;
use Pulsar\Query;

/**
 * Driver for storing models in a database using PDO.
 */
final class DatabaseDriver extends AbstractDriver
{
    private ConnectionManager $connections;
    private QueryBuilder $connection;
    private int $transactionNestingLevel = 0;

    /**
     * Sets the connection manager.
     *
     * @return $this
     */
    public function setConnectionManager(ConnectionManager $manager): self
    {
        $this->connections = $manager;

        return $this;
    }

    /**
     * Gets the connection manager.
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connections;
    }

    /**
     * Sets the default database connection.
     *
     * @return $this
     */
    public function setConnection(QueryBuilder $db): self
    {
        $this->connection = $db;

        return $this;
    }

    /**
     * Gets the database connection.
     *
     * @param string|null $id connection ID
     *
     * @throws DriverException when the connection has not been set yet
     */
    public function getConnection(?string $id): QueryBuilder
    {
        if (isset($this->connections)) {
            try {
                if ($id) {
                    return $this->connections->get($id);
                }

                return $this->connections->getDefault();
            } catch (JAQBException|PDOException $e) {
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (!isset($this->connection)) {
            throw new DriverException('The database driver has not been given a connection!');
        }

        return $this->connection;
    }

    public function createModel(Model $model, array $parameters): bool
    {
        $values = $this->serialize($parameters, $model);
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        try {
            return $db->insert($values)
                ->into($tablename)
                ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver when creating the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function getCreatedId(Model $model, string $propertyName): mixed
    {
        try {
            return $this->getConnection($model->getConnection())->lastInsertId();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver when getting the ID of the new '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function loadModel(Model $model): ?array
    {
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        try {
            $row = $db->select('*')
                ->from($tablename)
                ->where($model->ids())
                ->one();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver when loading an instance of '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    public function updateModel(Model $model, array $parameters): bool
    {
        if (0 == count($parameters)) {
            return true;
        }

        $values = $this->serialize($parameters, $model);
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        try {
            return $db->update($tablename)
                ->values($values)
                ->where($model->ids())
                ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver when updating the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function deleteModel(Model $model): bool
    {
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        try {
            return $db->delete($tablename)
                ->where($model->ids())
                ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while deleting the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function queryModels(Query $query): array
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();

        // build a DB query from the model query
        $dbQuery = $this->getConnection($model->getConnection())
            ->select($this->prefixSelect('*', $tablename))
            ->from($tablename)
            ->limit($query->getLimit(), $query->getStart())
            ->orderBy($this->prefixSort($query->getSort(), $tablename));

        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return $dbQuery->all();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while performing the '.$model::modelName().' query: '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function count(Query $query): int
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        $dbQuery = $db->select()
            ->count()
            ->from($tablename);
        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return (int) $dbQuery->scalar();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while getting the number of '.$model::modelName().' objects: '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function sum(Query $query, string $field): float
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        $dbQuery = $db->select()
            ->sum($this->prefixColumn($field, $tablename))
            ->from($tablename);
        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return (float) $dbQuery->scalar();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while getting the sum of '.$model::modelName().' '.$field.': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function average(Query $query, string $field): float
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        $dbQuery = $db->select()
            ->average($this->prefixColumn($field, $tablename))
            ->from($tablename);
        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return (float) $dbQuery->scalar();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while getting the average of '.$model::modelName().' '.$field.': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function max(Query $query, string $field): float
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        $dbQuery = $db->select()
            ->max($this->prefixColumn($field, $tablename))
            ->from($tablename);
        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return (float) $dbQuery->scalar();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while getting the max of '.$model::modelName().' '.$field.': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function min(Query $query, string $field): float
    {
        $modelClass = $query->getModel();
        $model = new $modelClass();
        $tablename = $model->getTablename();
        $db = $this->getConnection($model->getConnection());

        $dbQuery = $db->select()
            ->min($this->prefixColumn($field, $tablename))
            ->from($tablename);
        $this->addWhere($query, $tablename, $dbQuery);
        $this->addJoins($query, $tablename, $dbQuery);

        try {
            return (float) $dbQuery->scalar();
        } catch (PDOException $original) {
            throw new DriverException('An error occurred in the database driver while getting the min of '.$model::modelName().' '.$field.': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function startTransaction(?string $connection): void
    {
        if (0 == $this->transactionNestingLevel) {
            $db = $this->getConnection($connection);
            if ($db->inTransaction()) {
                $this->transactionNestingLevel += 2;

                return;
            }

            $db->beginTransaction();
        }

        ++$this->transactionNestingLevel;
    }

    public function rollBackTransaction(?string $connection): void
    {
        if (0 == $this->transactionNestingLevel) {
            throw new DriverException('No active transaction');
        } elseif (1 == $this->transactionNestingLevel) {
            $this->getConnection($connection)->rollBack();
        }

        --$this->transactionNestingLevel;
    }

    public function commitTransaction(?string $connection): void
    {
        if (0 == $this->transactionNestingLevel) {
            throw new DriverException('No active transaction');
        } elseif (1 == $this->transactionNestingLevel) {
            $this->getConnection($connection)->commit();
        }

        --$this->transactionNestingLevel;
    }
}
