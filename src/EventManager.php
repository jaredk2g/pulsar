<?php

namespace Pulsar;

use Pulsar\Event\AbstractEvent;
use Pulsar\Exception\ListenerException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Manages the event bus for model lifecycle events.
 */
class EventManager
{
    /** @var array */
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

    /**
     * Subscribes to a listener to an event.
     *
     * @param string $event    event name
     * @param int    $priority optional priority, higher #s get called first
     */
    public static function listen(string $class, string $event, callable $listener, int $priority = 0): void
    {
        self::getDispatcher($class)->addListener($event, $listener, $priority);
    }

    /**
     * Dispatches the given event and checks if it was successful.
     * If it fails then any active transaction will be rolled back
     * on the model.
     *
     * @return bool true if the events were successfully propagated
     */
    public static function dispatch(Model $model, AbstractEvent $event, bool $usesTransactions): bool
    {
        // Model events can fail whenever $event->stopPropagation() is called
        // or when a specific exception type is thrown by the listener.
        try {
            self::getDispatcher(get_class($model))->dispatch($event, $event::NAME);
        } catch (ListenerException $e) {
            // Listener exceptions provide the error message to be stored on the model
            if ($message = $e->getMessage()) {
                $model->getErrors()->add($e->getMessage(), $e->getContext());
            }
            $event->stopPropagation();
        }

        if (!$event->isPropagationStopped()) {
            return true;
        }

        // when listeners fail roll back any database transaction
        if ($usesTransactions) {
            $model::getDriver()->rollBackTransaction($model->getConnection());
        }

        return false;
    }
}
