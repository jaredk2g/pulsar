<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Model;
use Pulsar\ModelEvent;

class ModelEventTest extends MockeryTestCase
{
    public function testGetModel()
    {
        $model = Mockery::mock(Model::class);
        $event = new ModelEvent($model);
        $this->assertEquals($model, $event->getModel());
    }
}
