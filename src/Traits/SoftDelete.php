<?php

namespace Pulsar\Traits;

use BadMethodCallException;
use Pulsar\Query;
use Pulsar\Type;
use Pulsar\Validator;

/**
 * Allows models to be soft deleted.
 *
 * @property bool     $deleted
 * @property int|null $deleted_at
 */
trait SoftDelete
{
    protected static function autoDefinitionSoftDelete(): void
    {
        static::$properties['deleted'] = [
            'type' => Type::BOOLEAN,
        ];
        static::$properties['deleted_at'] = [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
            'null' => true,
        ];
    }

    protected function performDelete(): bool
    {
        $updateArray = [
            'deleted' => true,
            'deleted_at' => time(),
        ];
        foreach ($updateArray as $k => &$v) {
            Validator::validateProperty($this, static::definition()->get($k), $v);
        }

        $updated = static::getDriver()->updateModel($this, $updateArray);
        if ($updated) {
            $this->_values['deleted'] = true;
            $this->_values['deleted_at'] = time();
        }

        return $updated;
    }

    /**
     * Restores a soft-deleted model.
     */
    public function restore(): bool
    {
        if (!$this->deleted) {
            throw new BadMethodCallException('restore() can only be called on a deleted model');
        }

        $this->deleted = false;
        $this->deleted_at = null;

        return $this->save();
    }

    /**
     * Generates a new query instance that excludes soft-deleted models.
     */
    public static function withoutDeleted(): Query
    {
        return static::query()->where('deleted', false);
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }
}
