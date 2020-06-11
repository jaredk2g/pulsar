<?php

namespace Pulsar\Validation;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Encrypts a string value using defuse/php-encryption.
 *
 * Options:
 * - key: encryption key (required)
 */
class Encrypt implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $value = Crypto::encrypt($value, Key::loadFromAsciiSafeString($options['key']));

        return true;
    }
}
