<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a number.
 *
 * Options:
 * - type: specifies a PHP type to validate with is_* (defaults to numeric)
 */
class Numeric implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        if (!isset($options['type'])) {
            return is_numeric($value);
        }

        $check = 'is_'.$options['type'];

        return $check($value);
    }
}
