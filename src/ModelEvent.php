<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use Symfony\Contracts\EventDispatcher\Event;

final class ModelEvent extends Event
{
    const CREATING = 'model.creating';
    const CREATED = 'model.created';
    const UPDATING = 'model.updating';
    const UPDATED = 'model.updated';
    const DELETING = 'model.deleting';
    const DELETED = 'model.deleted';

    /**
     * @var Model
     */
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Gets the model for this event.
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
