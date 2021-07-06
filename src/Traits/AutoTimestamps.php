<?php

namespace Pulsar\Traits;

use Pulsar\Event\AbstractEvent;
use Pulsar\Event\ModelCreating;
use Pulsar\Type;

/**
 * Installs `created_at` and `updated_at` properties on the model.
 *
 * @property int $created_at
 * @property int $updated_at
 */
trait AutoTimestamps
{
    protected function autoInitializeAutoTimestamps(): void
    {
        self::saving([static::class, 'setAutoTimestamps']);
    }

    protected static function autoDefinitionAutoTimestamps(): void
    {
        static::$properties['created_at'] = [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ];
        static::$properties['updated_at'] = [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ];
    }

    public static function setAutoTimestamps(AbstractEvent $event): void
    {
        $model = $event->getModel();

        if ($event instanceof ModelCreating) {
            $model->created_at = time();
        }

        $model->updated_at = time();
    }
}
