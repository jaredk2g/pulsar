<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a URL.
 */
class Url implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }
}
