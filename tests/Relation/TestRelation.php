<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests\Relation;

use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Relation\Relation;

class TestRelation extends Relation
{
    protected function initQuery(Query $query)
    {
        $query->where('test', true);

        return $query;
    }

    public function getResults()
    {
        // do nothing
    }

    public function save(Model $model)
    {
        // do nothing
    }

    public function create(array $values = [])
    {
        // do nothing
    }
}
