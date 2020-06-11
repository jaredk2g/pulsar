<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates that an array of values matches. The array will
 * be flattened to a single value if it matches.
 */
class Matching implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        if (!is_array($value)) {
            return true;
        }

        $matches = true;
        $cur = reset($value);
        foreach ($value as $v) {
            $matches = ($v == $cur) && $matches;
            $cur = $v;
        }

        if ($matches) {
            $value = $cur;
        }

        return $matches;
    }
}
