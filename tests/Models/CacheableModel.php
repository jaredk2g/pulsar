<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Traits\Cacheable;

class CacheableModel extends Model
{
    use Cacheable;

    public static $cacheTTL = 10;
}
