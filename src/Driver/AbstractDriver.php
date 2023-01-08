<?php

namespace Pulsar\Driver;

use BackedEnum;
use DateTimeInterface;
use JAQB\Query\SelectQuery;
use Pulsar\Model;
use Pulsar\Property;
use Pulsar\Query;
use Pulsar\Type;
use UnitEnum;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * Marshals a value to storage.
     */
    public function serializeValue(mixed $value, ?Property $property): mixed
    {
        // encode backed enums as their backing type
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // encode datetime objects
        if ($value instanceof DateTimeInterface) {
            $format = $property?->date_format;
            if (!$format) {
                $format = $property?->type == Type::DATE ? 'Y-m-d' : 'Y-m-d H:i:s';
            }

            return $value->format($format);
        }

        // encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Serializes an array of values.
     */
    protected function serialize(array $values, Model $model): array
    {
        foreach ($values as $k => &$value) {
            $value = $this->serializeValue($value, $model::definition()->get($k));
        }

        return $values;
    }

    /**
     * Returns a prefixed select statement.
     */
    protected function prefixSelect(string $columns, string $tablename): string
    {
        $prefixed = [];
        foreach (explode(',', $columns) as $column) {
            $prefixed[] = $this->prefixColumn($column, $tablename);
        }

        return implode(',', $prefixed);
    }

    /**
     * Returns a prefixed where statement.
     */
    protected function prefixWhere(array $where, string $tablename): array
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
     */
    protected function prefixSort(array $sort, string $tablename): array
    {
        foreach ($sort as &$condition) {
            $condition[0] = $this->prefixColumn($condition[0], $tablename);
        }

        return $sort;
    }

    /**
     * Prefix columns with tablename that contains only
     * alphanumeric/underscores/*.
     */
    protected function prefixColumn(string $column, string $tablename): string
    {
        if ('*' === $column || preg_match('/^[a-z0-9_]+$/i', $column)) {
            return "$tablename.$column";
        }

        return $column;
    }

    /**
     * Adds join conditions to a select query.
     */
    protected function addJoins(Query $query, string $tablename, SelectQuery $dbQuery): void
    {
        foreach ($query->getJoins() as $join) {
            [$foreignModelClass, $column, $foreignKey, $type] = $join;

            $foreignModel = new $foreignModelClass();
            $foreignTablename = $foreignModel->getTablename();
            $condition = $this->prefixColumn($column, $tablename).'='.$this->prefixColumn($foreignKey, $foreignTablename);

            $dbQuery->join($foreignTablename, $condition, null, $type);
        }
    }
}
