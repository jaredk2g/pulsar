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

use Pulsar\Errors;

/**
 * Error stack for Infuse framework.
 */
class ErrorStack
{
    public function __invoke($app)
    {
        $errors = new Errors();

        if (isset($app['locale'])) {
            $errors->setLocale($app['locale']);
        }

        return $errors;
    }
}
