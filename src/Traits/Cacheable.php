<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Traits;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Adds a caching layer to a model that is queried before refresh() is called.
 */
trait Cacheable
{
    private static ?CacheItemPoolInterface $cachePool = null;
    private static array $cachePrefix = [];
    private ?CacheItemInterface $cacheItem = null;

    /**
     * Sets the default cache instance used by new models.
     */
    public static function setCachePool(CacheItemPoolInterface $pool): void
    {
        self::$cachePool = $pool;
    }

    /**
     * Clears the default cache instance for all models.
     */
    public static function clearCachePool(): void
    {
        self::$cachePool = null;
    }

    /**
     * Returns the cache instance.
     */
    public function getCachePool(): ?CacheItemPoolInterface
    {
        return self::$cachePool;
    }

    /**
     * Returns the cache TTL.
     */
    public function getCacheTTL(): ?int
    {
        return (property_exists($this, 'cacheTTL')) ? static::$cacheTTL : 86400; // default = 1 day
    }

    /**
     * Returns the cache key for this model.
     */
    public function getCacheKey(): string
    {
        $k = get_called_class();
        if (!isset(self::$cachePrefix[$k])) {
            self::$cachePrefix[$k] = 'models.'.strtolower(static::modelName());
        }

        return self::$cachePrefix[$k].'.'.$this->id();
    }

    /**
     * Returns the cache item for this model.
     */
    public function getCacheItem(): ?CacheItemInterface
    {
        if (!self::$cachePool) {
            return null;
        }

        if (!$this->cacheItem) {
            $k = $this->getCacheKey();
            $this->cacheItem = self::$cachePool->getItem($k);
        }

        return $this->cacheItem;
    }

    public function refresh(): self
    {
        if (!$this->hasId()) {
            return $this;
        }

        if (self::$cachePool) {
            // Attempt to load the model from the caching layer first.
            // If that fails, then fall through to the data layer.
            $item = $this->getCacheItem();

            if ($item->isHit()) {
                // load the values directly instead of using
                // refreshWith() to prevent triggering another
                // cache call
                $this->_persisted = true;
                $this->_values = $item->get();

                // clear any relationships
                $this->_relationships = [];

                return $this;
            }
        }

        return parent::refresh();
    }

    public function refreshWith(array $values): self
    {
        return parent::refreshWith($values)->cache();
    }

    /**
     * Caches the entire model.
     *
     * @return $this
     */
    public function cache(): self
    {
        if (!self::$cachePool || 0 == count($this->_values)) {
            return $this;
        }

        // cache the local properties
        $item = $this->getCacheItem();
        $item->set($this->_values)
            ->expiresAfter($this->getCacheTTL());

        self::$cachePool->save($item);

        return $this;
    }

    public function clearCache(): self
    {
        if (self::$cachePool) {
            $k = $this->getCacheKey();
            self::$cachePool->deleteItem($k);
        }

        return parent::clearCache();
    }
}
