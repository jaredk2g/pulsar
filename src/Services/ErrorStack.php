<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Services;

/**
 * Class ErrorStack.
 */
class ErrorStack
{
    public function __invoke($app)
    {
        $errors = new \Pulsar\ErrorStack();

        if (isset($app['locale'])) {
            $errors->setLocale($app['locale']);
        }

        return $errors;
    }
}
