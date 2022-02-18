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

final class Iterator implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $pointer;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int|bool
     */
    private $loadedStart;

    /**
     * @var array
     */
    private $models;

    /**
     * @var int|bool
     */
    private $count;

    public function __construct(Query $query)
    {
        $this->query = $query;
        $this->models = [];
        $this->start = $query->getStart();
        $this->limit = $query->getLimit();
        $this->pointer = $this->start;

        $sort = $query->getSort();
        if (empty($sort)) {
            $model = $query->getModel();
            $idProperties = $model::getIDProperties();
            foreach ($idProperties as &$property) {
                $property .= ' asc';
            }

            $query->sort(implode(',', $idProperties));
        }
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    //////////////////////////
    // Iterator Interface
    //////////////////////////

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind(): void
    {
        $this->pointer = $this->start;
        $this->loadedStart = false;
        $this->models = [];
        $this->count = false;
    }

    /**
     * Returns the current element.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if ($this->pointer >= $this->count()) {
            return null;
        }

        $this->loadModels();
        $k = $this->pointer % $this->limit;

        if (isset($this->models[$k])) {
            return $this->models[$k];
        }

        return null;
    }

    /**
     * Return the key of the current element.
     */
    public function key(): int
    {
        return $this->pointer;
    }

    /**
     * Move forward to the next element.
     */
    public function next(): void
    {
        ++$this->pointer;
    }

    /**
     * Checks if current position is valid.
     */
    public function valid(): bool
    {
        return $this->pointer < $this->count();
    }

    //////////////////////////
    // Countable Interface
    //////////////////////////

    /**
     * Get total number of models matching query.
     */
    public function count(): int
    {
        $this->updateCount();

        return $this->count;
    }

    //////////////////////////
    // ArrayAccess Interface
    //////////////////////////

    public function offsetExists($offset): bool
    {
        return is_numeric($offset) && $offset < $this->count();
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("$offset does not exist on this Iterator");
        }

        $this->pointer = $offset;

        return $this->current();
    }

    public function offsetSet($offset, $value): void
    {
        // iterators are immutable
        throw new \Exception('Cannot perform set on immutable Iterator');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        // iterators are immutable
        throw new \Exception('Cannot perform unset on immutable Iterator');
    }

    //////////////////////////
    // Private Methods
    //////////////////////////

    /**
     * Load the next round of models.
     */
    private function loadModels()
    {
        $start = $this->rangeStart($this->pointer, $this->limit);
        if ($this->loadedStart !== $start) {
            $this->query->start($start);

            $this->models = $this->query->execute();
            $this->loadedStart = $start;
        }
    }

    /**
     * Updates the total count of models. For better performance
     * the count is only updated on edges, which is when new models
     * need to be loaded.
     */
    private function updateCount()
    {
        // The count only needs to be updated when the pointer is
        // on the edges
        if (0 != $this->pointer % $this->limit &&
            $this->pointer < $this->count) {
            return;
        }

        $newCount = $this->query->count();

        // It's possible when iterating over models that something
        // is modified or deleted that causes the model count
        // to decrease. If the count has decreased then we
        // shift the pointer to prevent overflow.
        // This calculation is based on the assumption that
        // the first N (count - count') models are deleted.
        if (0 != $this->count && $newCount < $this->count) {
            $this->pointer = max(0, $this->pointer - ($this->count - $newCount));
        }

        // If the count has increased then the pointer is still
        // valid. Update the count to include the extra models.
        $this->count = $newCount;
    }

    /**
     * Generates the starting page given a pointer and limit.
     */
    private function rangeStart(int $pointer, int $limit)
    {
        return floor($pointer / $limit) * $limit;
    }

    /**
     * Cast Iterator to array.
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
