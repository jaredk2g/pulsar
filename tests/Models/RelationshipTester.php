<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class RelationshipTester extends Model
{
    protected static function getProperties(): array
    {
        return [
            'belongs_to_legacy' => new Property(
                relation: TestModel2::class,
            ),
            'belongs_to' => new Property(
                belongs_to: TestModel2::class,
            ),
            'belongs_to_many' => new Property(
                belongs_to_many: TestModel2::class,
            ),
            'has_one' => new Property(
                has_one: TestModel2::class,
            ),
            'has_many' => new Property(
                has_many: TestModel2::class,
            ),
            'polymorphic' => new Property(
                morphs_to: [
                    'card' => Card::class,
                    'bank_account' => BankAccount::class,
                ],
            ),
            'name' => new Property(),
        ];
    }
}
