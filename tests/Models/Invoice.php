<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Invoice extends Model
{
    protected static function getProperties(): array
    {
        return [
            'customer' => new Property([
                'belongs_to' => Customer::class,
                'required' => true,
            ]),
        ];
    }
}
