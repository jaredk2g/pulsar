<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Exception\DriverException;

class DriverExceptionTest extends MockeryTestCase
{
    public function testException()
    {
        $e = new Exception();
        $ex = new DriverException();
        $ex->setException($e);
        $this->assertEquals($e, $ex->getException());
    }
}
