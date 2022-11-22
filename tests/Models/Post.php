<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class Post extends Model
{
    protected static function getProperties(): array
    {
        return [
            'category' => new Property(
                belongs_to: Category::class,
            ),
        ];
    }
}
