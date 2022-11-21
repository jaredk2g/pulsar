<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Category extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => [],
            'posts' => [
                'has_many' => Post::class,
            ],
        ];
    }
}
