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

/**
 * @template T
 */
final class Iterator implements \Iterator, \Countable, \ArrayAccess
{
    /** @var Query<T> */
    private Query $query;
    private int $start;
    private int $pointer;
    private int $limit;
    private int|bool $loadedStart = false;
    /** @var T[] */
    private array $models = [];
    private int|bool $count = false;

    /**
     * @param Query<T>
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
        $this->start = $query->getStart();
        $this->limit = $query->getLimit();
        $this->pointer = $this->start;

        $sort = $query->getSort();
        if (empty($sort)) {
            $model = $query->getModel();
            $idProperties = $model::definition()->getIds();
            foreach ($idProperties as &$property) {
                $property .= ' asc';
            }

            $query->sort(implode(',', $idProperties));
        }
    }

    /**
     * @return Query<T>
     */
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
     * @return T
     */
    #[\ReturnTypeWillChange]
    public function current(): mixed
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
        // The only way that we can know if the current position
        // is valid is to attempt to load the current position.
        // This may result in a database call due to lazy loading.
        // We cannot trust the count here in case the data is being
        // inserted or deleted during iteration.
        return null !== $this->current();
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

    /**
     * @return T
     */
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
    private function loadModels(): void
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
    private function updateCount(): void
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
    private function rangeStart(int $pointer, int $limit): int
    {
        return floor($pointer / $limit) * $limit;
    }

    /**
     * Cast Iterator to array.
     *
     * @return T[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
