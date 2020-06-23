<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Calls a custom validation function.
 *
 * Options:
 * - fn: specifies a callable value (required)
 */
class Callables implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return $options['fn']($value, $options, $model);
    }
}
