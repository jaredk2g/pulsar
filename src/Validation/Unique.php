<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Checks if a value is unique for a property.
 *
 * Options:
 * - column: specifies which column must be unique (required)
 */
class Unique implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $name = $options['column'];
        if (!$model->dirty($name, true)) {
            return true;
        }

        return 0 == $model::query()->where([$name => $value])->count();
    }
}
