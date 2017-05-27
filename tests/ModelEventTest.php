<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Pulsar\Model;
use Pulsar\ModelEvent;

class ModelEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $model = Mockery::mock(Model::class);
        $event = new ModelEvent($model);
        $this->assertEquals($model, $event->getModel());
    }
}
