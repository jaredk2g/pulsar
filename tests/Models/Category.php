<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Category extends Model
{
    protected static $properties = [
        'name' => [],
        'posts' => [
            'relation' => Post::class,
            'relation_type' => Model::RELATIONSHIP_HAS_MANY,
        ],
    ];
}
