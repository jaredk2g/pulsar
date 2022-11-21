<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;

class Post extends Model
{
    protected static function getProperties(): array
    {
        return [
            'category' => [
                'belongs_to' => Category::class,
            ],
        ];
    }
}
