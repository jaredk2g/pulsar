<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates an alpha-numeric string.
 *
 * Options:
 * - min: specifies a minimum length
 */
class AlphaNumeric implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $minLength = $options['min'] ?? 0;

        return preg_match('/^[A-Za-z0-9]*$/', $value) && strlen($value) >= $minLength;
    }
}
