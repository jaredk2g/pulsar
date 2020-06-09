<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Relation\Relationship;

class Post extends Model
{
    protected static $properties = [
        'category' => [
            'relation' => Category::class,
            'relation_type' => Relationship::BELONGS_TO,
        ],
    ];
}
