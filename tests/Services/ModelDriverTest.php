<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Application;
use JAQB\ConnectionManager;
use JAQB\QueryBuilder;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Errors;
use Pulsar\Model;
use Pulsar\Services\ModelDriver;

class ModelDriverTest extends PHPUnit_Framework_TestCase
{
    public function testInvokeConnectionManager()
    {
        $config = [
            'models' => [
                'driver' => DatabaseDriver::class,
            ],
        ];
        $app = new Application($config);
        $errorStack = new Errors();
        $app['errors'] = function () use ($errorStack) {
            return $errorStack;
        };
        $app['database'] = function () {
            return new ConnectionManager();
        };
        $service = new ModelDriver($app);
        $this->assertInstanceOf(DatabaseDriver::class, Model::getDriver());

        $driver = $service($app);
        $this->assertInstanceOf(DatabaseDriver::class, $driver);
        $this->assertInstanceOf(ConnectionManager::class, $driver->getConnectionManager());

        $model = new TestModel();
        $this->assertEquals($errorStack, $model->getErrors());
    }

    public function testInvokeLegacy()
    {
        $config = [
            'models' => [
                'driver' => DatabaseDriver::class,
            ],
        ];
        $app = new Application($config);
        $errorStack = new Errors();
        $app['errors'] = function () use ($errorStack) {
            return $errorStack;
        };
        $app['db'] = function () {
            return new QueryBuilder();
        };
        $service = new ModelDriver($app);
        $this->assertInstanceOf(DatabaseDriver::class, Model::getDriver());

        $driver = $service($app);
        $this->assertInstanceOf(DatabaseDriver::class, $driver);
        $this->assertInstanceOf(QueryBuilder::class, $driver->getConnection(false));

        $model = new TestModel();
        $this->assertEquals($errorStack, $model->getErrors());
    }
}
