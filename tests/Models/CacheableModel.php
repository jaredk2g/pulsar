<?php

namespace Pulsar\Tests\Models;

use Pulsar\Cacheable;
use Pulsar\Model;

class CacheableModel extends Model
{
    use Cacheable;

    public static $cacheTTL = 10;
}
