<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Invoice extends Model
{
    public static $properties = [
        'customer' => [
            'belongs_to' => Customer::class,
            'required' => true,
        ],
    ];
}
