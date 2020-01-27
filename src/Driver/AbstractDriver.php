<?php

namespace Pulsar\Driver;

use JAQB\Query\SelectQuery;
use Pulsar\Query;

abstract class AbstractDriver implements DriverInterface
{
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
    protected function serialize(array $values)
    {
        foreach ($values as &$value) {
            $value = $this->serializeValue($value);
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
    protected function prefixSelect($columns, $tablename)
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
    protected function prefixWhere(array $where, $tablename)
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
    protected function prefixSort(array $sort, $tablename)
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
    protected function prefixColumn($column, $tablename)
    {
        if ('*' === $column || preg_match('/^[a-z0-9_]+$/i', $column)) {
            return "$tablename.$column";
        }

        return $column;
    }

    /**
     * Adds join conditions to a select query.
     *
     * @param Query       $query
     * @param string      $tablename
     * @param SelectQuery $dbQuery
     */
    protected function addJoins(Query $query, $tablename, $dbQuery)
    {
        foreach ($query->getJoins() as $join) {
            list($foreignModelClass, $column, $foreignKey) = $join;

            $foreignModel = new $foreignModelClass();
            $foreignTablename = $foreignModel->getTablename();
            $condition = $this->prefixColumn($column, $tablename).'='.$this->prefixColumn($foreignKey, $foreignTablename);

            $dbQuery->join($foreignTablename, $condition);
        }
    }
}
