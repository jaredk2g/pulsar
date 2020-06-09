<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;

class Category extends Model
{
    protected static $properties = [
        'name' => [],
        'posts' => [
            'relation' => Post::class,
            'relation_type' => Relationship::HAS_MANY,
        ],
    ];
}
