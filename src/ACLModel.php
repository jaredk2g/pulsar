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

use Pulsar\Event\ModelCreating;
use Pulsar\Event\ModelDeleting;
use Pulsar\Event\ModelUpdating;

/**
 * Class ACLModel.
 */
abstract class ACLModel extends Model
{
    const ERROR_NO_PERMISSION = 'no_permission';
    const LISTENER_PRIORITY = 1000;

    private array $permissionsCache = [];
    private bool $permissionsDisabled = false;

    /**
     * Checks if the requesting model has a specific permission
     * on this object.
     */
    public function can(string $permission, Model $requester): bool
    {
        if ($this->permissionsDisabled) {
            return true;
        }

        // cache when checking permissions
        $k = $permission.'.'.get_class($requester).'.'.$requester->id();
        if (!isset($this->permissionsCache[$k])) {
            $this->permissionsCache[$k] = $this->hasPermission($permission, $requester);
        }

        return $this->permissionsCache[$k];
    }

    /**
     * Checks if a requester has a specific permission.
     */
    abstract protected function hasPermission(string $permission, Model $requester): bool;

    /**
     * Disables all permissions checking in can() for this object
     * DANGER: this should only be used when objects are mutated from application code
     * Granting all permissions to anyone else, i.e. HTTP requests is dangerous.
     *
     * @return $this
     */
    public function grantAllPermissions(): self
    {
        $this->permissionsDisabled = true;

        return $this;
    }

    /**
     * Ensures that permissions are enforced for this object.
     *
     * @return $this
     */
    public function enforcePermissions(): self
    {
        $this->permissionsDisabled = false;

        return $this;
    }

    protected function initialize(): void
    {
        parent::initialize();

        // check the if the requester has the `create`
        // permission before creating
        static::creating([self::class, 'checkCreatePermission'], self::LISTENER_PRIORITY);

        // check the if the requester has the `edit`
        // permission before updating
        static::updating([self::class, 'checkUpdatePermission'], self::LISTENER_PRIORITY);

        // check the if the requester has the `delete`
        // permission before deleting
        static::deleting([self::class, 'checkDeletePermission'], self::LISTENER_PRIORITY);
    }

    public static function checkCreatePermission(ModelCreating $event): void
    {
        $model = $event->getModel();

        if (!$model->can('create', ACLModelRequester::get())) {
            $model->getErrors()->add(ACLModel::ERROR_NO_PERMISSION);

            $event->stopPropagation();
        }
    }

    public static function checkUpdatePermission(ModelUpdating $event): void
    {
        $model = $event->getModel();

        if (!$model->can('edit', ACLModelRequester::get())) {
            $model->getErrors()->add(ACLModel::ERROR_NO_PERMISSION);

            $event->stopPropagation();
        }
    }

    public static function checkDeletePermission(ModelDeleting $event): void
    {
        $model = $event->getModel();

        if (!$model->can('delete', ACLModelRequester::get())) {
            $model->getErrors()->add(ACLModel::ERROR_NO_PERMISSION);

            $event->stopPropagation();
        }
    }
}
