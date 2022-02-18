<?php

namespace Pulsar\Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\ACLModel;
use Pulsar\ACLModelRequester;
use Pulsar\Driver\DriverInterface;
use Pulsar\Tests\Models\Person;

class ACLModelRequesterTest extends MockeryTestCase
{
    public static function setUpBeforeClass(): void
    {
        $driver = Mockery::mock(DriverInterface::class);
        ACLModel::setDriver($driver);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        ACLModelRequester::clear();
    }

    public function testGet()
    {
        $this->assertNull(ACLModelRequester::get());

        $requester = new Person(['id' => 2]);
        ACLModelRequester::set($requester);
        $this->assertEquals($requester, ACLModelRequester::get());

        ACLModelRequester::clear();
        $this->assertNull(ACLModelRequester::get());

        $requester2 = new Person(['id' => 3]);
        ACLModelRequester::set($requester2);
        $this->assertEquals($requester2, ACLModelRequester::get());
    }

    public function testGetCallable()
    {
        $i = 3;
        ACLModelRequester::setCallable(function () use (&$i) {
            ++$i;

            return new Person(['id' => $i]);
        });

        // callable should only fire once
        for ($j = 0; $j < 5; ++$j) {
            $requester = ACLModelRequester::get();
            $this->assertInstanceOf(Person::class, $requester);
            $this->assertEquals(4, $requester->id());
        }

        // set should override the callable
        $requester = new Person(['id' => 2]);
        ACLModelRequester::set($requester);
        $this->assertEquals($requester, ACLModelRequester::get());

        ACLModelRequester::clear();

        for ($j = 0; $j < 5; ++$j) {
            $requester = ACLModelRequester::get();
            $this->assertInstanceOf(Person::class, $requester);
            $this->assertEquals(5, $requester->id());
        }
    }
}
