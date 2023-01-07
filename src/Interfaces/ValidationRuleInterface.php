<?php

namespace Pulsar\Interfaces;

use Pulsar\Model;

interface ValidationRuleInterface
{
    /**
     * Validates and formats a property value according to the rule implementation.
     */
    public function validate(mixed &$value, array $options, Model $model): bool;
}
