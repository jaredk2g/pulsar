<?php

namespace Pulsar\Interfaces;

use Pulsar\Model;

interface ValidationRuleInterface
{
    /**
     * Validates and formats a property value according to the rule implementation.
     *
     * @param mixed $value
     */
    public function validate(&$value, array $options, Model $model): bool;
}
