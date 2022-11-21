<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Customer extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => [],
            'payment_method' => [
                'morphs_to' => [
                    'card' => Card::class,
                    'bank_account' => BankAccount::class,
                ],
            ],
        ];
    }
}
