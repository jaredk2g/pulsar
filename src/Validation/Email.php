<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates an e-mail address.
 */
class Email implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $value = trim(strtolower($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
