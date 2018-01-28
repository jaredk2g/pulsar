<?php

namespace Pulsar;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;

/**
 * Represents a collection of models.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var Model[]
     */
    private $models;

    /**
     * @param Model[] $models
     */
    public function __construct(array $models)
    {
        $this->models = $models;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->models);
    }

    public function offsetExists($offset)
    {
        return isset($this->models[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->models[$offset];
    }

    public function offsetSet($offset, $value)
    {
        // collections are immutable
        throw new Exception('Cannot perform set on immutable Collection');
    }

    public function offsetUnset($offset)
    {
        // collections are immutable
        throw new Exception('Cannot perform unset on immutable Collection');
    }

    public function count()
    {
        return count($this->models);
    }
}
