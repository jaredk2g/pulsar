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

use DateTimeZone;
use Exception;

/**
 * Validates one or more fields based upon certain filters.
 * Filters may be chained and will be executed in order:
 * i.e. new Validate('email') or new Validate('matching|password:8|required').
 *
 * NOTE: some filters may modify the data, which is passed in by reference
 */
class Validator
{
    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var array|string
     */
    private $rules;

    /**
     * @var string
     */
    private $failingRule;

    /**
     * Changes settings for the validator.
     *
     * @param array $config
     */
    public static function configure($config)
    {
        self::$config = array_replace(self::$config, (array) $config);
    }

    /**
     * @param array|string $rules can be key-value array matching data or a string
     */
    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    /**
     * Gets the rules.
     *
     * @return array|string
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Validates the given data against the rules.
     *
     * @param array|mixed $data can be key-value array matching rules or a single value
     */
    public function validate(&$data): bool
    {
        if (is_array($this->rules)) {
            $validated = true;

            foreach ($this->rules as $key => $rule) {
                $result = self::validateRule($data[$key], $rule);
                $validated = $validated && $result;
            }

            return $validated;
        }

        return self::validateRule($data, $this->rules);
    }

    /**
     * Gets the failing rule.
     *
     * @return string|false
     */
    public function getFailingRule()
    {
        return $this->failingRule;
    }

    /**
     * Validates a value according to a rule or set of rules.
     * This will short-circuit on the first failing rule.
     *
     * @param mixed  $value
     * @param string $rule  rule string
     */
    private function validateRule(&$value, $rule): bool
    {
        $filters = explode('|', $rule);

        foreach ($filters as $filterStr) {
            $exp = explode(':', $filterStr);
            $filter = $exp[0];
            $result = $this->$filter($value, array_slice($exp, 1));

            if (!$result) {
                $this->failingRule = $filter;

                return false;
            }
        }

        return true;
    }

    ////////////////////////////////
    // FILTERS
    ////////////////////////////////

    /**
     * Validates an alpha string.
     * OPTIONAL alpha:5 can specify minimum length.
     *
     * @param mixed $value
     */
    private function alpha(&$value, array $parameters): bool
    {
        $minLength = $parameters[0] ?? 0;

        return preg_match('/^[A-Za-z]*$/', $value) && strlen($value) >= $minLength;
    }

    /**
     * Validates an alpha-numeric string
     * OPTIONAL alpha_numeric:6 can specify minimum length.
     *
     * @param mixed $value
     */
    private function alpha_numeric(&$value, array $parameters): bool
    {
        $minLength = $parameters[0] ?? 0;

        return preg_match('/^[A-Za-z0-9]*$/', $value) && strlen($value) >= $minLength;
    }

    /**
     * Validates an alpha-numeric string with dashes and underscores
     * OPTIONAL alpha_dash:7 can specify minimum length.
     *
     * @param mixed $value
     */
    private function alpha_dash(&$value, array $parameters): bool
    {
        $minLength = $parameters[0] ?? 0;

        return preg_match('/^[A-Za-z0-9_-]*$/', $value) && strlen($value) >= $minLength;
    }

    /**
     * Validates a boolean value.
     *
     * @param mixed $value
     */
    private function boolean(&$value): bool
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return true;
    }

    /**
     * Validates an e-mail address.
     *
     * @param string $email      e-mail address
     * @param array  $parameters parameters for validation
     *
     * @return bool success
     */
    private function email(&$value, array $parameters): bool
    {
        $value = trim(strtolower($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates a value exists in an array. i.e. enum:blue,red,green,yellow.
     *
     * @param mixed $value
     */
    private function enum(&$value, array $parameters): bool
    {
        $enum = explode(',', $parameters[0]);

        return in_array($value, $enum);
    }

    /**
     * Validates a date string.
     *
     * @param mixed $value
     */
    private function date(&$value): bool
    {
        return strtotime($value);
    }

    /**
     * Validates an IP address.
     *
     * @param mixed $value
     */
    private function ip(&$value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * Validates that an array of values matches. The array will
     * be flattened to a single value if it matches.
     *
     * @param mixed $value
     */
    private function matching(&$value): bool
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

    /**
     * Validates a number.
     * OPTIONAL numeric:int specifies a type.
     *
     * @param mixed $value
     */
    private function numeric(&$value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return is_numeric($value);
        }

        $check = 'is_'.$parameters[0];

        return $check($value);
    }

    /**
     * Validates a password and hashes the value using
     * password_hash().
     * OPTIONAL password:10 sets the minimum length.
     *
     * @param mixed $value
     */
    private function password_php(&$value, array $parameters): bool
    {
        $minimumPasswordLength = (isset($parameters[0])) ? $parameters[0] : 8;

        if (strlen($value) < $minimumPasswordLength) {
            return false;
        }

        $parameters = [];
        if (isset(self::$config['password_cost'])) {
            $parameters['cost'] = self::$config['password_cost'];
        }

        $value = password_hash($value, PASSWORD_DEFAULT, $parameters);

        return true;
    }

    /**
     * Validates that a number falls within a range.
     *
     * @param mixed $value
     */
    private function range(&$value, array $parameters): bool
    {
        // check min
        if (isset($parameters[0]) && $value < $parameters[0]) {
            return false;
        }

        // check max
        if (isset($parameters[1]) && $value > $parameters[1]) {
            return false;
        }

        return true;
    }

    /**
     * Makes sure that a variable is not empty.
     *
     * @param mixed $value
     */
    private function required(&$value): bool
    {
        return !empty($value);
    }

    /**
     * Validates a string.
     * OPTIONAL string:5 supplies a minimum length
     *          string:1:5 supplies a minimum and maximum length.
     *
     * @param mixed $value
     */
    private function string(&$value, array $parameters): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $len = strlen($value);
        $min = $parameters[0] ?? 0;
        $max = $parameters[1] ?? null;

        return $len >= $min && (!$max || $len <= $max);
    }

    /**
     * Validates a PHP time zone identifier.
     *
     * @param mixed $value
     */
    private function time_zone(&$value): bool
    {
        try {
            $tz = new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Validates a Unix timestamp. If the value is not a timestamp it will be
     * converted to one with `strtotime()`.
     *
     * @param mixed $value
     */
    private function timestamp(&$value): bool
    {
        if (ctype_digit((string) $value)) {
            return true;
        }

        $value = strtotime($value);

        return (bool) $value;
    }

    /**
     * Converts a Unix timestamp into a format compatible with database
     * timestamp types.
     *
     * @param mixed $value
     */
    private function db_timestamp(&$value): bool
    {
        if (is_integer($value)) {
            // MySQL datetime format
            $value = date('Y-m-d H:i:s', $value);

            return true;
        }

        return false;
    }

    /**
     * Validates a URL.
     *
     * @param mixed $value
     */
    private function url(&$value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }
}
