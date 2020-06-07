<?php

namespace Pulsar\Tests;

use PHPUnit\Framework\TestCase;
use Pulsar\Model;
use Pulsar\Property;

class PropertyTest extends TestCase
{
    public function testArrayAccess()
    {
        $property = new Property(['required' => true]);
        $this->assertFalse(isset($property['does_not_exist']));
        $this->assertNull($property['does_not_exist']);

        $this->assertTrue(isset($property['required']));
        $this->assertTrue($property['required']);

        $e = null;
        try {
            $property['test'] = ['required' => true];
        } catch (\Exception $ex) {
            $e = $ex;
        }
        $this->assertNotNull($e);

        $e = null;
        try {
            unset($property['test']);
        } catch (\Exception $ex) {
            $e = $ex;
        }
        $this->assertNotNull($e);
    }

    public function testMutable()
    {
        $property = new Property(['mutable' => Model::MUTABLE]);
        $this->assertTrue($property->isMutable());
        $this->assertFalse($property->isImmutable());
        $this->assertFalse($property->isMutableCreateOnly());

        $property = new Property(['mutable' => Model::IMMUTABLE]);
        $this->assertFalse($property->isMutable());
        $this->assertTrue($property->isImmutable());
        $this->assertFalse($property->isMutableCreateOnly());

        $property = new Property(['mutable' => Model::MUTABLE_CREATE_ONLY]);
        $this->assertFalse($property->isMutable());
        $this->assertFalse($property->isImmutable());
        $this->assertTrue($property->isMutableCreateOnly());
    }
}
