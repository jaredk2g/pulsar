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

use Pulsar\Model;
use Pulsar\Validate;

class ModelDriver
{
    /**
     * @var Pulsar\Driver\DriverInterface
     */
    private $driver;

    public function __construct($app)
    {
        // make the app available to models
        Model::inject($app);

        // set up the model driver
        $config = $app['config'];
        $class = $config->get('models.driver');
        $this->driver = new $class($app);
        Model::setDriver($this->driver);

        // used for password hasing
        Validate::configure(['salt' => $config->get('app.salt')]);
    }

    public function __invoke()
    {
        return $this->driver;
    }
}
