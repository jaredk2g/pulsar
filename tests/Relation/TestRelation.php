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
    protected function initQuery(Query $query): Query
    {
        $query->where('test', true);

        return $query;
    }

    public function getResults()
    {
        // do nothing
    }

    public function save(Model $model): Model
    {
        // do nothing
        return $model;
    }

    public function create(array $values = []): Model
    {
        // do nothing
        return new \TestModel();
    }
}
