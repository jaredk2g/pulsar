<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Application;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Services\ErrorStack;

class ErrorStackServiceTest extends MockeryTestCase
{
    public function testInvoke()
    {
        $app = new Application();
        $service = new ErrorStack();
        $errors = $service($app);
        $this->assertInstanceOf('Pulsar\Errors', $errors);
    }
}
