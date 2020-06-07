<?php

namespace Pulsar\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use JAQB\Query\DeleteQuery;
use JAQB\Query\InsertQuery;
use JAQB\Query\SelectQuery;
use JAQB\Query\UpdateQuery;
use Pulsar\Exception\DriverException;
use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Type;

class DbalDriver extends AbstractDriver
{
    /**
     * @var Connection
     */
    private $database;

    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }

    public function getConnection($connection): Connection
    {
        if ($connection) {
            throw new DriverException('Currently multiple connections are not supported');
        }

        return $this->database;
    }

    public function createModel(Model $model, array $parameters)
    {
        // build the SQL query
        $tablename = $model->getTablename();
        $values = $this->serialize($parameters);
        $dbQuery = new InsertQuery();
        $dbQuery->into($tablename)->values($values);

        // then execute the query through DBAL
        $db = $this->getConnection($model->getConnection());

        try {
            $db->executeQuery($dbQuery->build(), $dbQuery->getValues());

            return true;
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver when creating the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function getCreatedID(Model $model, string $propertyName)
    {
        try {
            $id = $this->getConnection($model->getConnection())->lastInsertId();
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver when getting the ID of the new '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }

        return Type::cast($model::getProperty($propertyName), $id);
    }

    public function loadModel(Model $model): ?array
    {
        // build the SQL query
        $tablename = $model->getTablename();
        $dbQuery = new SelectQuery();
        $dbQuery->select('*')
            ->from($tablename)
            ->where($model->ids());

        // then execute the query through DBAL
        $db = $this->getConnection($model->getConnection());

        try {
            $row = $db->fetchAssoc($dbQuery->build(), $dbQuery->getValues());
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver when loading an instance of '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    public function queryModels(Query $query): array
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->select($this->prefixSelect('*', $tablename))
            ->limit($query->getLimit(), $query->getStart())
            ->orderBy($this->prefixSort($query->getSort(), $tablename));

        // then execute the query through DBAL
        $db = $this->getConnection($model->getConnection());

        try {
            return $db->fetchAll($dbQuery->build(), $dbQuery->getValues());
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver while performing the '.$model::modelName().' query: '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function updateModel(Model $model, array $parameters): bool
    {
        if (0 == count($parameters)) {
            return true;
        }

        // build the SQL query
        $tablename = $model->getTablename();
        $values = $this->serialize($parameters);
        $dbQuery = new UpdateQuery();
        $dbQuery->table($tablename)
            ->values($values)
            ->where($model->ids());

        // then execute the query through DBAL
        $db = $this->getConnection($model->getConnection());

        try {
            $db->executeQuery($dbQuery->build(), $dbQuery->getValues());

            return true;
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver when updating the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function deleteModel(Model $model): bool
    {
        // build the SQL query
        $tablename = $model->getTablename();
        $dbQuery = new DeleteQuery();
        $dbQuery->from($tablename)
            ->where($model->ids());

        // then execute the query through DBAL
        $db = $this->getConnection($model->getConnection());

        try {
            $db->executeQuery($dbQuery->build(), $dbQuery->getValues());

            return true;
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver while deleting the '.$model::modelName().': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function count(Query $query): int
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->count();

        // then execute the query through DBAL
        return (int) $this->executeScalar($dbQuery, $model, 'count');
    }

    public function sum(Query $query, string $field)
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->sum($this->prefixColumn($field, $tablename));

        // then execute the query through DBAL
        return (int) $this->executeScalar($dbQuery, $model, $field);
    }

    public function average(Query $query, string $field)
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->average($this->prefixColumn($field, $tablename));

        // then execute the query through DBAL
        return (int) $this->executeScalar($dbQuery, $model, $field);
    }

    public function max(Query $query, string $field)
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->max($this->prefixColumn($field, $tablename));

        // then execute the query through DBAL
        return (int) $this->executeScalar($dbQuery, $model, $field);
    }

    public function min(Query $query, string $field)
    {
        // build the SQL query
        $modelClass = $query->getModel();
        $model = new $modelClass();

        $tablename = $model->getTablename();
        $dbQuery = $this->buildSelectQuery($query, $tablename);
        $dbQuery->min($this->prefixColumn($field, $tablename));

        // then execute the query through DBAL
        return (int) $this->executeScalar($dbQuery, $model, $field);
    }

    //////////////////////////
    /// Helpers
    //////////////////////////

    /**
     * Builds a new select query.
     */
    private function buildSelectQuery(Query $query, string $tablename): SelectQuery
    {
        $dbQuery = new SelectQuery();
        $dbQuery->from($tablename)
            ->where($this->prefixWhere($query->getWhere(), $tablename));

        $this->addJoins($query, $tablename, $dbQuery);

        return $dbQuery;
    }

    /**
     * Executes a select query through DBAL and returns a scalar result.
     *
     * @throws DriverException
     *
     * @return false|mixed
     */
    private function executeScalar(SelectQuery $query, Model $model, string $field)
    {
        $db = $this->getConnection($model->getConnection());

        try {
            return $db->fetchColumn($query->build(), $query->getValues());
        } catch (DBALException $original) {
            throw new DriverException('An error occurred in the database driver while getting the value of '.$model::modelName().'.'.$field.': '.$original->getMessage(), $original->getCode(), $original);
        }
    }

    public function startTransaction(?string $connection): void
    {
        $this->getConnection($connection)->beginTransaction();
    }

    public function rollBackTransaction(?string $connection): void
    {
        $this->getConnection($connection)->rollBack();
    }

    public function commitTransaction(?string $connection): void
    {
        $this->getConnection($connection)->commit();
    }
}
