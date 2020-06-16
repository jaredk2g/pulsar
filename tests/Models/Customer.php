<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Customer extends Model
{
    public static $properties = [
        'name' => [],
        'payment_method' => [
            'morphs_to' => [
                'card' => Card::class,
                'bank_account' => BankAccount::class,
            ],
        ],
    ];
}
