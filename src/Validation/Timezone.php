<?php

namespace Pulsar\Validation;

use DateTimeZone;
use Exception;
use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a PHP time zone identifier.
 */
class Timezone implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        try {
            $tz = new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
