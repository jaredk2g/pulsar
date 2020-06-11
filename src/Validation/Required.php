<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Makes sure that a variable is not empty.
 */
class Required implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return !empty($value);
    }
}
