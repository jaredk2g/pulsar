<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates an alpha-numeric string with dashes and underscores.
 *
 * Options:
 * - min: specifies a minimum length
 */
class AlphaDash implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $minLength = $options['min'] ?? 0;

        return preg_match('/^[A-Za-z0-9_-]*$/', $value) && strlen($value) >= $minLength;
    }
}
