<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pimple\Container;
use Psr\Cache\CacheItemInterface;
use Pulsar\Driver\DriverInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

require_once 'tests/test_models.php';

class CacheableTest extends PHPUnit_Framework_TestCase
{
    public static $app;

    public static function setUpBeforeClass()
    {
        // set up DI
        self::$app = new Container();

        CacheableModel::inject(self::$app);
    }

    protected function tearDown()
    {
        CacheableModel::clearCachePool();
    }

    private function getCache()
    {
        return new ArrayAdapter();
    }

    public function testGetCachePool()
    {
        $cache = $this->getCache();

        CacheableModel::setCachePool($cache);
        for ($i = 0; $i < 5; ++$i) {
            $model = new CacheableModel();
            $this->assertEquals($cache, $model->getCachePool());
        }
    }

    public function testGetCacheTTL()
    {
        $model = new CacheableModel();
        $this->assertEquals(10, $model->getCacheTTL());
    }

    public function testGetCacheKey()
    {
        $model = new CacheableModel(5);
        $this->assertEquals('models.cacheablemodel.5', $model->getCacheKey());
    }

    public function testGetCacheItem()
    {
        $cache = $this->getCache();
        CacheableModel::setCachePool($cache);

        $model = new CacheableModel(5);
        $item = $model->getCacheItem();
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertEquals('models.cacheablemodel.5', $item->getKey());

        $model = new CacheableModel(6);
        $item = $model->getCacheItem();
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertEquals('models.cacheablemodel.6', $item->getKey());
    }

    public function testRefreshCached()
    {
        $cache = $this->getCache();

        $model = new CacheableModel(100);
        CacheableModel::setCachePool($cache);

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('loadModel')
               ->andReturn(['answer' => 42])
               ->once();

        CacheableModel::setDriver($driver);

        // the first refresh() call should be a miss
        // the data layer because no caching has been performed
        $this->assertEquals($model, $model->refresh());
        $this->assertEquals(42, $model->answer);

        // values should now be cached
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertTrue($item->isHit());
        $expected = ['answer' => 42];
        $this->assertEquals($expected, $value);

        // the next refresh() call should be a hit from the cache
        $model = new CacheableModel(100);
        $this->assertEquals($model, $model->refresh());
        $this->assertEquals(42, $model->answer);
    }

    public function testRefreshNoCachePool()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('loadModel')
            ->andReturn(['answer' => 42]);
        CacheableModel::setDriver($driver);

        $model = new CacheableModel(5);
        $this->assertNull($model->getCachePool());
        $this->assertNull($model->getCacheItem());
        $this->assertEquals($model, $model->refresh());
        $this->assertEquals(42, $model->answer);
    }

    public function testRefreshNoId()
    {
        $model = new CacheableModel();
        $this->assertEquals($model, $model->refresh());
    }

    public function testCache()
    {
        $cache = $this->getCache();
        CacheableModel::setCachePool($cache);

        $model = new CacheableModel(102, ['id' => 102, 'answer' => 42]);

        // cache
        $this->assertEquals($model, $model->cache());
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertTrue($item->isHit());

        // clear the cache
        $this->assertEquals($model, $model->clearCache());
        $item = $cache->getItem($model->getCacheKey());
        $value = $item->get();
        $this->assertFalse($item->isHit());
    }
}
