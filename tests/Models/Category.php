<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Category extends Model
{
    protected static $properties = [
        'name' => [],
        'posts' => [
            'has_many' => Post::class,
        ],
    ];
}
