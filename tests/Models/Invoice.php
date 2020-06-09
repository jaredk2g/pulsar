<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;

class Invoice extends Model
{
    public static $properties = [
        'customer' => [
            'relation' => Customer::class,
            'relation_type' => Relationship::BELONGS_TO,
        ],
    ];
}
