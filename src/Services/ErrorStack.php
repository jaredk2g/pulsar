<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Pulsar\Services;

class ErrorStack
{
    public function __invoke($app)
    {
        return new \Pulsar\ErrorStack($app);
    }
}
