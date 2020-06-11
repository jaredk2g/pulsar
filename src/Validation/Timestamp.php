<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a Unix timestamp. If the value is not a timestamp it will be
 * converted to one with `strtotime()`.
 */
class Timestamp implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        if (ctype_digit((string) $value)) {
            return true;
        }

        $value = strtotime($value);

        return (bool) $value;
    }
}
