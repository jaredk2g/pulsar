<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates that a number falls within a range.
 *
 * Options:
 * - min: minimum value that is valid
 * - max: maximum value that is valid
 */
class Range implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        // check min
        if (isset($options['min']) && $value < $options['min']) {
            return false;
        }

        // check max
        if (isset($options['max']) && $value > $options['max']) {
            return false;
        }

        return true;
    }
}
