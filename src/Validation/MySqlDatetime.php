<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Converts a Unix timestamp into a format compatible with database
 * timestamp types.
 */
class MySqlDatetime implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        if (is_integer($value)) {
            // MySQL datetime format
            $value = date('Y-m-d H:i:s', $value);

            return true;
        }

        return false;
    }
}
