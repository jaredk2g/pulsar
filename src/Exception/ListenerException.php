<?php

namespace Pulsar\Exception;

/**
 * An exception thrown by a model listener that also contains
 * the error message to be set on the model.
 */
class ListenerException extends ModelException
{
    private array $context;

    /**
     * @param string $message the error message to be provided to the model
     * @param array  $context the context for the model error message
     */
    public function __construct(string $message = '', array $context = [])
    {
        parent::__construct($message);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
