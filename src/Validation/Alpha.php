<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates an alpha string.
 *
 * Options:
 * - min: specifies a minimum length
 */
class Alpha implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $minLength = $options['min'] ?? 0;

        return preg_match('/^[A-Za-z]*$/', $value) && strlen($value) >= $minLength;
    }
}
