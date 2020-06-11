<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a date string.
 */
class Date implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return strtotime($value);
    }
}
