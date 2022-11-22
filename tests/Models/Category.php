<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Category extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => new Property(),
            'posts' => new Property(
                has_many: Post::class,
            ),
        ];
    }
}
