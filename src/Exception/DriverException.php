<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Exception;

use Exception;

/**
 * Class DriverException.
 */
class DriverException extends ModelException
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
