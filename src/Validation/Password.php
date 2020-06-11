<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a password and hashes the value using
 * password_hash().
 *
 * Options:
 * - min: minimum password length
 * - cost: desired cost used to generate hash
 */
class Password implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $minimumPasswordLength = $options['min'] ?? 8;

        if (strlen($value) < $minimumPasswordLength) {
            return false;
        }

        $hashOptions = [];
        if (isset($options['cost'])) {
            $hashOptions['cost'] = $options['cost'];
        }

        $value = password_hash($value, PASSWORD_DEFAULT, $hashOptions);

        return true;
    }
}
