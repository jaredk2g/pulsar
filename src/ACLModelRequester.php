<?php

namespace Pulsar;

/**
 * Holds the requesting model for ACL
 * permission checks.
 */
final class ACLModelRequester
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
     */
    public static function setCallable(callable $requesterCallable): void
    {
        self::$requesterCallable = $requesterCallable;
    }

    /**
     * Sets the current requester.
     */
    public static function set(Model $requester): void
    {
        self::$requester = $requester;
    }

    /**
     * Clears the current requester.
     */
    public static function clear(): void
    {
        self::$requester = null;
    }

    /**
     * Gets the current requester.
     */
    public static function get(): ?Model
    {
        if (!self::$requester && self::$requesterCallable) {
            self::$requester = call_user_func(self::$requesterCallable);
        }

        return self::$requester;
    }
}
