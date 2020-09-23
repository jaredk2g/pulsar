<?php

namespace Pulsar;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Manages the event bus for model lifecycle events.
 */
class EventManager
{
    /**
     * @var array
     */
    private static $dispatchers = [];

    /**
     * Gets the event dispatcher.
     */
    public static function getDispatcher(string $class): EventDispatcher
    {
        if (!isset(self::$dispatchers[$class])) {
            self::$dispatchers[$class] = new EventDispatcher();
        }

        return self::$dispatchers[$class];
    }

    /**
     * Resets the event dispatcher for a given class.
     */
    public static function reset(string $class): void
    {
        if (isset(self::$dispatchers[$class])) {
            unset(self::$dispatchers[$class]);
        }
    }
}
