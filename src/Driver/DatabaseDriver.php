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

use ICanBoogie\Inflector;
use JAQB\QueryBuilder;
use PDOException;
use PDOStatement;
use Pimple\Container;
use Pulsar\Exception\DriverException;
use Pulsar\Model;
use Pulsar\Query;

/**
 * Driver for storing models in a database using PDO.
 */
class DatabaseDriver implements DriverInterface
{
    /**
     * @var QueryBuilder
     */
    private $connection;

    /**
     * @var Container
     */
    private $container;

    /**
     * Sets the database connection.
     *
     * @param QueryBuilder $db
     *
     * @return self
     */
    public function setConnection(QueryBuilder $db)
    {
        $this->connection = $db;

        return $this;
    }

    /**
     * Gets the database connection.
     *
     * @return QueryBuilder
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->container['db'];
        }

        return $this->connection;
    }

    /**
     * @deprecated
     *
     * Sets the DI container
     *
     * @param Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @deprecated
     *
     * Gets the DI container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function createModel(Model $model, array $parameters)
    {
        $values = $this->serialize($parameters);
        $tablename = $this->getTablename($model);
        $db = $this->getConnection();

        try {
            return $db->insert($values)
                      ->into($tablename)
                      ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver when creating the '.$model::modelName().': '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }
    }

    public function getCreatedID(Model $model, $propertyName)
    {
        try {
            $id = $this->getConnection()->getPDO()->lastInsertId();
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver when getting the ID of the new '.$model::modelName().': '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }

        return Model::cast($model::getProperty($propertyName), $id);
    }

    public function loadModel(Model $model)
    {
        $tablename = $this->getTablename($model);
        $db = $this->getConnection();

        try {
            $row = $db->select('*')
                      ->from($tablename)
                      ->where($model->ids())
                      ->one();
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver when loading an instance of '.$model::modelName().': '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }

        if (!is_array($row)) {
            return false;
        }

        return $this->unserialize($row, $model::getProperties());
    }

    public function updateModel(Model $model, array $parameters)
    {
        if (count($parameters) == 0) {
            return true;
        }

        $values = $this->serialize($parameters);
        $tablename = $this->getTablename($model);
        $db = $this->getConnection();

        try {
            return $db->update($tablename)
                      ->values($values)
                      ->where($model->ids())
                      ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver when updating the '.$model::modelName().': '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }
    }

    public function deleteModel(Model $model)
    {
        $tablename = $this->getTablename($model);
        $db = $this->getConnection();

        try {
            return $db->delete($tablename)
                      ->where($model->ids())
                      ->execute() instanceof PDOStatement;
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver while deleting the '.$model::modelName().': '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }
    }

    public function queryModels(Query $query)
    {
        $model = $query->getModel();
        $tablename = $this->getTablename($model);

        // build a DB query from the model query
        $dbQuery = $this->getConnection()
            ->select($this->prefixSelect('*', $tablename))
            ->from($tablename)
            ->where($this->prefixWhere($query->getWhere(), $tablename))
            ->limit($query->getLimit(), $query->getStart())
            ->orderBy($this->prefixSort($query->getSort(), $tablename));

        // join conditions
        foreach ($query->getJoins() as $join) {
            list($foreignModel, $column, $foreignKey) = $join;

            $foreignTablename = $this->getTablename($foreignModel);
            $condition = $this->prefixColumn($column, $tablename).'='.$this->prefixColumn($foreignKey, $foreignTablename);

            $dbQuery->join($foreignTablename, $condition);
        }

        try {
            $data = $dbQuery->all();
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver while performing the '.$model::modelName().' query: '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }

        $properties = $model::getProperties();
        foreach ($data as &$row) {
            $row = $this->unserialize($row, $properties);
        }

        return $data;
    }

    public function totalRecords(Query $query)
    {
        $model = $query->getModel();
        $tablename = $this->getTablename($model);
        $db = $this->getConnection();

        try {
            return (int) $db->select('count(*)')
                            ->from($tablename)
                            ->where($query->getWhere())
                            ->scalar();
        } catch (PDOException $original) {
            $e = new DriverException('An error occurred in the database driver while getting the number of '.$model::modelName().' objects: '.$original->getMessage());
            $e->setException($original);
            throw $e;
        }
    }

    /**
     * Generates the tablename for the model.
     *
     * @param string|Model $model
     *
     * @return string
     */
    public function getTablename($model)
    {
        $inflector = Inflector::get();

        return $inflector->camelize($inflector->pluralize($model::modelName()));
    }

    /**
     * Marshals a value to storage.
     *
     * @param mixed $value
     *
     * @return mixed serialized value
     */
    public function serializeValue($value)
    {
        // encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Serializes an array of values.
     *
     * @param array $values
     *
     * @return array
     */
    private function serialize(array $values)
    {
        foreach ($values as &$value) {
            $value = $this->serializeValue($value);
        }

        return $values;
    }

    /**
     * Unserializes an array of values.
     *
     * @param array $values
     * @param array $properties model properties
     *
     * @return array
     */
    private function unserialize(array $values, array $properties)
    {
        foreach ($values as $k => &$value) {
            if (isset($properties[$k])) {
                $value = Model::cast($properties[$k], $value);
            }
        }

        return $values;
    }

    /**
     * Returns a prefixed select statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return string
     */
    private function prefixSelect($columns, $tablename)
    {
        $prefixed = [];
        foreach (explode(',', $columns) as $column) {
            $prefixed[] = $this->prefixColumn($column, $tablename);
        }

        return implode(',', $prefixed);
    }

    /**
     * Returns a prefixed where statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return array
     */
    private function prefixWhere(array $where, $tablename)
    {
        $return = [];
        foreach ($where as $key => $condition) {
            // handles $where[property] = value
            if (!is_numeric($key)) {
                $return[$this->prefixColumn($key, $tablename)] = $condition;
            // handles $where[] = [property, value, '=']
            } elseif (is_array($condition)) {
                $condition[0] = $this->prefixColumn($condition[0], $tablename);
                $return[] = $condition;
            // handles raw SQL - do nothing
            } else {
                $return[] = $condition;
            }
        }

        return $return;
    }

    /**
     * Returns a prefixed sort statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return array
     */
    private function prefixSort(array $sort, $tablename)
    {
        foreach ($sort as &$condition) {
            $condition[0] = $this->prefixColumn($condition[0], $tablename);
        }

        return $sort;
    }

    /**
     * Prefix columns with tablename that contains only
     * alphanumeric/underscores/*.
     *
     * @param string $column
     * @param string $tablename
     *
     * @return string prefixed column
     */
    private function prefixColumn($column, $tablename)
    {
        if ($column === '*' || preg_match('/^[a-z0-9_]+$/i', $column)) {
            return "$tablename.$column";
        }

        return $column;
    }
}
