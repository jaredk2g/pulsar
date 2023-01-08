<?php

namespace Pulsar\Tests;

use PHPUnit\Framework\TestCase;
use Pulsar\Property;

class PropertyTest extends TestCase
{
    public function testDefault()
    {
        $property = new Property();
        $this->assertFalse($property->hasDefault);

        $property = new Property(default: 'test');
        $this->assertTrue($property->hasDefault);

        $property = new Property(default: null);
        $this->assertTrue($property->hasDefault);

        $property = new Property(default: false);
        $this->assertTrue($property->hasDefault);

        $property = new Property(default: 0);
        $this->assertTrue($property->hasDefault);

        $property = new Property(default: '');
        $this->assertTrue($property->hasDefault);

        $property = new Property(default: []);
        $this->assertTrue($property->hasDefault);
    }

    public function testMutable()
    {
        $property = new Property(mutable: Property::MUTABLE);
        $this->assertTrue($property->isMutable());
        $this->assertFalse($property->isImmutable());
        $this->assertFalse($property->isMutableCreateOnly());

        $property = new Property(mutable: Property::IMMUTABLE);
        $this->assertFalse($property->isMutable());
        $this->assertTrue($property->isImmutable());
        $this->assertFalse($property->isMutableCreateOnly());

        $property = new Property(mutable: Property::MUTABLE_CREATE_ONLY);
        $this->assertFalse($property->isMutable());
        $this->assertFalse($property->isImmutable());
        $this->assertTrue($property->isMutableCreateOnly());
    }
}
