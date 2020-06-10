<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Post extends Model
{
    protected static $properties = [
        'category' => [
            'belongs_to' => Category::class,
        ],
    ];
}
