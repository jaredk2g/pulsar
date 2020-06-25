<?php

namespace Pulsar\Traits;

use Pulsar\Type;

/**
 * Installs `deleted_at` properties on the model.
 */
trait SoftDelete
{
    protected static function autoDefinitionSoftDelete(): void
    {
        static::$properties['deleted_at'] = [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
            'null' => true,
        ];
    }
}
