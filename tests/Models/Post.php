<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Post extends Model
{
    protected static $properties = [
        'category' => [
            'relation' => Category::class,
            'relation_type' => self::RELATIONSHIP_BELONGS_TO,
        ],
    ];
}
