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

use Pulsar\Driver\DatabaseDriver;
use Pulsar\Model;
use Pulsar\Validate;

/**
 * Class ModelDriver.
 */
class ModelDriver
{
    /**
     * @var \Pulsar\Driver\DriverInterface
     */
    private $driver;

    public function __construct($app)
    {
        // make the app available to models
        Model::inject($app);
        Model::setErrorStack($app['errors']);

        // set up the model driver
        $config = $app['config'];
        $class = $config->get('models.driver');
        $this->driver = new $class();

        if ($this->driver instanceof DatabaseDriver && isset($app['db'])) {
            $this->driver->setConnection($app['db']);
        }

        Model::setDriver($this->driver);

        // used for password hashing
        Validate::configure(['salt' => $config->get('app.salt')]);
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
