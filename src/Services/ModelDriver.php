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

use Infuse\Application;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Errors;
use Pulsar\Model;
use Pulsar\Validator;

/**
 * Class ModelDriver.
 */
class ModelDriver
{
    /**
     * @var \Pulsar\Driver\DriverInterface
     */
    private $driver;

    public function __construct(Application $app)
    {
        // set up the model driver
        $config = $app['config'];
        $class = $config->get('models.driver');
        $this->driver = new $class();

        if ($this->driver instanceof DatabaseDriver) {
            if (isset($app['database'])) {
                $this->driver->setConnectionManager($app['database']);
            }
        }

        Model::setDriver($this->driver);

        if (isset($app['locale'])) {
            Errors::setGlobalLocale($app['locale']);
        }

        // pass optional configuration to model validator
        Validator::configure($config->get('models.validator'));
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
