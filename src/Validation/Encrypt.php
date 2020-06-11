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
        $value = Crypto::encrypt($value, Type::getEncryptionKey());

        return true;
    }
}
