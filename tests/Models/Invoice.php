<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Invoice extends Model
{
    public static $properties = [
        'customer' => [
            'relation' => Customer::class,
            'relation_type' => self::RELATIONSHIP_BELONGS_TO,
        ],
    ];
}
