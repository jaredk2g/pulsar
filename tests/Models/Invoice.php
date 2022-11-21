<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Invoice extends Model
{
    protected static function getProperties(): array
    {
        return [
            'customer' => [
                'belongs_to' => Customer::class,
                'required' => true,
            ],
        ];
    }
}
