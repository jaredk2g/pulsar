<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use InvalidArgumentException;
use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Validation\Alpha;
use Pulsar\Validation\AlphaDash;
use Pulsar\Validation\AlphaNumeric;
use Pulsar\Validation\Boolean;
use Pulsar\Validation\Callables;
use Pulsar\Validation\Date;
use Pulsar\Validation\Email;
use Pulsar\Validation\Encrypt;
use Pulsar\Validation\Enum;
use Pulsar\Validation\Ip;
use Pulsar\Validation\Matching;
use Pulsar\Validation\MySqlDatetime;
use Pulsar\Validation\Numeric;
use Pulsar\Validation\Password;
use Pulsar\Validation\Range;
use Pulsar\Validation\Required;
use Pulsar\Validation\Strings;
use Pulsar\Validation\Timestamp;
use Pulsar\Validation\Timezone;
use Pulsar\Validation\Unique;
use Pulsar\Validation\Url;

/**
 * Validates one or more fields based upon certain filters.
 * Filters may be chained and will be executed in order:
 * i.e. new Validate('email') or new Validate('matching|password:8|required').
 *
 * NOTE: some filters may modify the data, which is passed in by reference
 */
final class Validator
{
    /**
     * @var string[]
     */
    private static array $validators = [
        'alpha' => Alpha::class,
        'alpha_dash' => AlphaDash::class,
        'alpha_numeric' => AlphaNumeric::class,
        'boolean' => Boolean::class,
        'callable' => Callables::class,
        'date' => Date::class,
        'db_timestamp' => MySqlDatetime::class,
        'encrypt' => Encrypt::class,
        'email' => Email::class,
        'enum' => Enum::class,
        'ip' => Ip::class,
        'matching' => Matching::class,
        'numeric' => Numeric::class,
        'password' => Password::class,
        'range' => Range::class,
        'required' => Required::class,
        'string' => Strings::class,
        'time_zone' => Timezone::class,
        'timestamp' => Timestamp::class,
        'unique' => Unique::class,
        'url' => Url::class,
    ];

    /** @var ValidationRuleInterface[] */
    private static array $instances = [];

    private array $rules;
    private ?string $failingRule = null;
    private ?array $failingOptions = null;

    /**
     * Rules can be defined in these formats:
     * - [['matching'], ['string', 'min' => '5']]
     * - ['matching', ['string', 'min' => '5']]
     * -  ['string', 'min' => '5']
     * - matching|password.
     */
    public function __construct(array|string $rules)
    {
        // parses this format: matching|password_php
        if (!is_array($rules)) {
            $rules = explode('|', $rules);
        }

        // parses this format: ['string', 'min' => 5]
        if (count($rules) > 0 && !is_array($rules[0]) && !isset($rules[1])) {
            $rules = [$rules];
        }

        foreach ($rules as &$rule) {
            if (!is_array($rule)) {
                $rule = [$rule];
            }
        }

        $this->rules = $rules;
    }

    /**
     * Gets the rules.
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Validates the given value against the rules.
     */
    public function validate(mixed &$value, Model $model): bool
    {
        foreach ($this->rules as $options) {
            $name = $options[0];
            unset($options[0]);

            if (!$this->getRule($name)->validate($value, $options, $model)) {
                $this->failingRule = $name;
                $this->failingOptions = $options;

                return false;
            }
        }

        return true;
    }

    /**
     * Gets the failing rule.
     */
    public function getFailingRule(): ?string
    {
        return $this->failingRule;
    }

    /**
     * Gets the options of the failing rule.
     */
    public function getFailingRuleOptions(): ?array
    {
        return $this->failingOptions;
    }

    private function getRule(string $name): ValidationRuleInterface
    {
        if (!isset(self::$instances[$name])) {
            if (!isset(self::$validators[$name])) {
                throw new InvalidArgumentException('Invalid validation rule: '.$name);
            }

            $class = self::$validators[$name];
            self::$instances[$name] = new $class();
        }

        return self::$instances[$name];
    }

    /**
     * Validates and marshals a property value prior to saving.
     */
    public static function validateProperty(Model $model, Property $property, mixed &$value): bool
    {
        // assume empty string is a null value for properties
        // that are marked as optionally-null
        if ($property->null && ('' === $value || null === $value)) {
            $value = null;

            return true;
        }

        $validationRules = $property->validate;
        if (!$validationRules) {
            return true;
        }

        $validator = new Validator($validationRules);
        if ($validator->validate($value, $model)) {
            return true;
        }

        // add a validation error message if one was not already added
        $errors = $model->getErrors();
        if (!$errors->has($property->name)) {
            $params = [
                'field' => $property->name,
                'field_name' => $property->getTitle($model),
                'rule' => $validator->getFailingRule(),
            ];
            $params = array_replace($validator->getFailingRuleOptions(), $params);
            $errors->add('pulsar.validation.'.$validator->getFailingRule(), $params);
        }

        return false;
    }
}
