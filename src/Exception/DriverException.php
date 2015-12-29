<?php

namespace Pulsar\Exception;

use Exception;

class DriverException
{
    /**
     * @var Exception
     */
    private $exception;

    /**
     * Sets the underlying exception.
     *
     * @param Exception $e
     */
    public function setException(Exception $e)
    {
        $this->exception = $e;
    }

    /**
     * Gets the underlying exception.
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
