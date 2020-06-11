<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a value exists in an array.
 *
 * Options:
 * - choices: specifies a list of valid choices (required)
 */
class Enum implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return in_array($value, $options['choices']);
    }
}
