<?php

use Pulsar\Exception\DriverException;

class DriverExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $e = new Exception();
        $ex = new DriverException();
        $ex->setException($e);
        $this->assertEquals($e, $ex->getException());
    }
}
