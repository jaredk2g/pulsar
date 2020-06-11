<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates an IP address.
 */
class Ip implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }
}
