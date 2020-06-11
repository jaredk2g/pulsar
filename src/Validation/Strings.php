<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a string.
 *
 * Options:
 * - min: specifies a minimum length
 * - max:  specifies a maximum length
 */
class Strings implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $len = strlen($value);
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? null;

        return $len >= $min && (!$max || $len <= $max);
    }
}
