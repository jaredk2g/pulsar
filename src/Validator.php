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

use Infuse\Utility;

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
    private static $config = [
        'salt' => '',
    ];

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
     *
     * @return bool
     */
    public function validate(&$data)
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
     * Validates a value according to as rule.
     *
     * @param mixed  $value
     * @param string $rule
     *
     * @return bool
     */
    private function validateRule(&$value, $rule)
    {
        $validated = true;

        $filters = explode('|', $rule);

        foreach ($filters as $filterStr) {
            $exp = explode(':', $filterStr);
            $filter = $exp[0];
            $result = $this->$filter($value, array_slice($exp, 1));

            if (!$result) {
                $validated = false;
                $this->failingRule = $filter;
            }
        }

        return $validated;
    }

    ////////////////////////////////
    // FILTERS
    ////////////////////////////////

    /**
     * Validates an alpha string.
     * OPTIONAL alpha:5 can specify minimum length.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function alpha(&$value, array $parameters)
    {
        return preg_match('/^[A-Za-z]*$/', $value) && strlen($value) >= array_value($parameters, 0);
    }

    /**
     * Validates an alpha-numeric string
     * OPTIONAL alpha_numeric:6 can specify minimum length.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function alpha_numeric(&$value, array $parameters)
    {
        return preg_match('/^[A-Za-z0-9]*$/', $value) && strlen($value) >= array_value($parameters, 0);
    }

    /**
     * Validates an alpha-numeric string with dashes and underscores
     * OPTIONAL alpha_dash:7 can specify minimum length.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function alpha_dash(&$value, array $parameters)
    {
        return preg_match('/^[A-Za-z0-9_-]*$/', $value) && strlen($value) >= array_value($parameters, 0);
    }

    /**
     * Validates a boolean value.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function boolean(&$value)
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
    private function email(&$value, array $parameters)
    {
        $value = trim(strtolower($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates a value exists in an array. i.e. enum:blue,red,green,yellow.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function enum(&$value, array $parameters)
    {
        $enum = explode(',', array_value($parameters, 0));

        return in_array($value, $enum);
    }

    /**
     * Validates a date string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function date(&$value)
    {
        return strtotime($value);
    }

    /**
     * Validates an IP address.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function ip(&$value)
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * Validates that an array of values matches. The array will
     * be flattened to a single value if it matches.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function matching(&$value)
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
     * @param array $parameters
     *
     * @return bool
     */
    private function numeric(&$value, array $parameters)
    {
        $check = 'is_'.array_value($parameters, 0);

        return (!isset($parameters[0])) ? is_numeric($value) : $check($value);
    }

    /**
     * Validates a password and hashes the value.
     * OPTIONAL password:10 sets the minimum length.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function password(&$value, array $parameters)
    {
        $minimumPasswordLength = (isset($parameters[0])) ? $parameters[0] : 8;

        if (strlen($value) < $minimumPasswordLength) {
            return false;
        }

        $value = Utility::encryptPassword($value, self::$config['salt']);

        return true;
    }

    /**
     * Validates that a number falls within a range.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function range(&$value, array $parameters)
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
     *
     * @return bool
     */
    private function required(&$value)
    {
        return !empty($value);
    }

    /**
     * Validates a string.
     * OPTIONAL string:5 supplies a minimum length
     *          string:1:5 supplies a minimum and maximum length.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    private function string(&$value, array $parameters)
    {
        if (!is_string($value)) {
            return false;
        }

        $len = strlen($value);
        $min = array_value($parameters, 0);
        $max = array_value($parameters, 1);

        return $len >= $min && (!$max || $len <= $max);
    }

    /**
     * Validates a PHP time zone identifier.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function time_zone(&$value)
    {
        // thanks to http://stackoverflow.com/questions/5816960/how-to-check-is-timezone-identifier-valid-from-code
        $valid = [];
        $tza = timezone_abbreviations_list();
        foreach ($tza as $zone) {
            foreach ($zone as $item) {
                $valid[$item['timezone_id']] = true;
            }
        }
        unset($valid['']);

        return (bool) array_value($valid, $value);
    }

    /**
     * Validates a Unix timestamp. If the value is not a timestamp it will be
     * converted to one with `strtotime()`.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function timestamp(&$value)
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
     *
     * @return bool
     */
    private function db_timestamp(&$value)
    {
        if (is_integer($value)) {
            $value = Utility::unixToDb($value);

            return true;
        }

        return false;
    }

    /**
     * Validates a URL.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function url(&$value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }
}
