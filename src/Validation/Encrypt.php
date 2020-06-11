<?php

namespace Pulsar\Validation;

use Defuse\Crypto\Crypto;
use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;
use Pulsar\Type;

/**
 * Encrypts a string value using defuse/php-encryption.
 *
 * In order for this validation rule to work it requires
 * that the defuse/php-encryption library is installed and
 * that the encryption key has been set with Type::setEncryptionKey().
 */
class Encrypt implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        // Encryption only works with strings. Convert to JSON if an object or array is given.
        if (is_object($value) || is_array($value)) {
            $value = json_encode($value);
        }

        $value = Crypto::encrypt($value, Type::getEncryptionKey());

        return true;
    }
}
