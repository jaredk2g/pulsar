<?php

/**
 * @package Pulsar
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2015 Jared King
 * @license MIT
 */

use Pulsar\ModelEvent;

class ModelEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetModel()
    {
        $model = Mockery::mock('Pulsar\Model');
        $event = new ModelEvent($model);
        $this->assertEquals($model, $event->getModel());
    }
}
