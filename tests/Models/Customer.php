<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Customer extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => new Property(),
            'payment_method' => new Property(
                morphs_to: [
                    'card' => Card::class,
                    'bank_account' => BankAccount::class,
                ],
            ),
        ];
    }
}
