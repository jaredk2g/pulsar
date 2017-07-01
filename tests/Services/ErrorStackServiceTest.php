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
use Pulsar\Services\ErrorStack;

class ErrorStackServiceTest extends PHPUnit_Framework_TestCase
{
    public function testInvoke()
    {
        $app = new Application();
        $service = new ErrorStack();
        $errors = $service($app);
        $this->assertInstanceOf('Pulsar\Errors', $errors);
    }
}
