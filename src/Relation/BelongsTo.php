<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Relation;

/**
 * Class BelongsTo.
 */
class BelongsTo extends Relation
{
    protected function initQuery()
    {
        $localKey = $this->localKey;

        $this->query->where([$this->foreignKey => $this->relation->$localKey])
                    ->limit(1);
    }

    public function getResults()
    {
        return $this->query->first();
    }
}
