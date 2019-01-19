<?php

namespace Pulsar;

/**
 * Holds the requesting model for ACL
 * permission checks.
 */
class ACLModelRequester
{
    /**
     * @var callable
     */
    private static $requesterCallable;

    /**
     * @var Model
     */
    private static $requester;

    /**
     * Sets the callable for getting the current requester.
     *
     * @param callable $requesterCallable
     */
    public static function setCallable(callable $requesterCallable)
    {
        self::$requesterCallable = $requesterCallable;
    }

    /**
     * Sets the current requester.
     *
     * @param Model $requester
     */
    public static function set(Model $requester)
    {
        self::$requester = $requester;
    }

    /**
     * Clears the current requester.
     */
    public static function clear()
    {
        self::$requester = null;
    }

    /**
     * Gets the current requester.
     *
     * @return Model|null
     */
    public static function get()
    {
        if (!self::$requester && self::$requesterCallable) {
            self::$requester = call_user_func(self::$requesterCallable);
        }

        return self::$requester;
    }
}
