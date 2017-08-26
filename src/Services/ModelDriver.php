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

    public function __construct($app)
    {
        // make the app available to models
        Model::inject($app);
        Model::setErrorStack($app['errors']);

        // set up the model driver
        $config = $app['config'];
        $class = $config->get('models.driver');
        $this->driver = new $class();

        if ($this->driver instanceof DatabaseDriver) {
            if (isset($app['database'])) {
                $this->driver->setConnectionManager($app['database']);
            } elseif (isset($app['db'])) {
                // NOTE this is kept around for backwards compatibility
                // but is no longer recommended
                $this->driver->setConnection($app['db']);
            }
        }

        Model::setDriver($this->driver);

        // pass optional configuration to model validator
        $validatorParams = [
            'salt' => $config->get('app.salt'), // DEPRECATED
        ];
        Validator::configure($config->get('models.validator', $validatorParams));
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
