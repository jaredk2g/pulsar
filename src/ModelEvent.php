<?php

/**
 * @package Pulsar
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use Symfony\Component\EventDispatcher\Event;

class ModelEvent extends Event
{
    const CREATING = 'model.creating';
    const CREATED = 'model.created';
    const UPDATING = 'model.updating';
    const UPDATED = 'model.updated';
    const DELETING = 'model.deleting';
    const DELETED = 'model.deleted';

    /**
     * @var \Pulsar\Model
     */
    protected $model;

    /**
     * @param \Pulsar\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Gets the model for this event.
     *
     * @return \Pulsar\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
