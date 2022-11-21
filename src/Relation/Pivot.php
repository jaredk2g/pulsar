<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Relation;

use Pulsar\Model;

/**
 * Pivot model shim for use by relationships.
 */
final class Pivot extends Model
{
    protected static $properties = [];

    /**
     * @var string
     */
    private $tablename;

    public function setTablename($tablename)
    {
        $this->tablename = $tablename;
    }

    public function getTablename(): string
    {
        return $this->tablename;
    }

    public function setProperties(string $localKey, string $foreignKey)
    {
        self::$properties = [
            $localKey => [],
            $foreignKey => [],
        ];
        $this->initialize();
    }

    protected static function getProperties(): array
    {
        return self::$properties;
    }
}
