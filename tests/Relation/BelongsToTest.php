<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests\Relation;

use Category;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Post;
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Pulsar\Relation\BelongsTo;

class BelongsToTest extends MockeryTestCase
{
    public static $driver;

    public static function setUpBeforeClass()
    {
        self::$driver = Mockery::mock(DriverInterface::class);
        Model::setDriver(self::$driver);
    }

    public function testInitQuery()
    {
        $post = new Post();
        $post->category_id = 10;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        $query = $relation->getQuery();
        $this->assertInstanceOf(Category::class, $query->getModel());
        $this->assertEquals(['id' => 10], $query->getWhere());
        $this->assertEquals(1, $query->getLimit());
    }

    public function testGetResults()
    {
        $post = new Post();
        $post->category_id = 10;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        self::$driver->shouldReceive('queryModels')
            ->andReturn([['id' => 11]]);

        $result = $relation->getResults();
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals(11, $result->id());
    }

    public function testEmpty()
    {
        $post = new Post();
        $post->category_id = null;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        $this->assertNull($relation->getResults());
    }

    public function testSave()
    {
        $post = new Post(100);
        $post->refreshWith(['category_id' => null]);

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        $category = new Category(20);
        $category->name = 'Test';

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$category, ['name' => 'Test']])
            ->andReturn(true)
            ->once();

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$post, ['category_id' => 20]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($category, $relation->save($category));

        $this->assertTrue($category->persisted());
        $this->assertTrue($post->persisted());
    }

    public function testCreate()
    {
        $post = new Post();
        $post->category_id = null;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        self::$driver->shouldReceive('createModel')
            ->andReturn(true)
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $category = $relation->create(['name' => 'Test']);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertTrue($category->persisted());

        $this->assertTrue($post->persisted());
    }

    public function testAttach()
    {
        $post = new Post();
        $post->category_id = null;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        $category = new Category(10);

        self::$driver->shouldReceive('createModel')
            ->withArgs([$post, ['category_id' => 10]])
            ->andReturn(true)
            ->once();

        self::$driver->shouldReceive('getCreatedID')
            ->andReturn(1);

        $this->assertEquals($relation, $relation->attach($category));
        $this->assertTrue($post->persisted());
    }

    public function testDetach()
    {
        $post = new Post();
        $post->category_id = 10;

        $relation = new BelongsTo($post, 'category_id', Category::class, 'id');

        self::$driver->shouldReceive('updateModel')
            ->withArgs([$post, ['category_id' => null]])
            ->andReturn(true)
            ->once();

        $this->assertEquals($relation, $relation->detach());
        $this->assertTrue($post->persisted());
    }
}
