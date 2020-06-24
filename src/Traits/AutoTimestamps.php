<?php

namespace Pulsar\Traits;

use Pulsar\Event\AbstractEvent;
use Pulsar\Event\ModelCreating;

trait AutoTimestamps
{
    public static function setAutoTimestamps(AbstractEvent $event): void
    {
        $model = $event->getModel();

        if ($event instanceof ModelCreating) {
            $model->created_at = time();
        }

        $model->updated_at = time();
    }
}
