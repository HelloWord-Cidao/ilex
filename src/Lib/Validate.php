<?php

namespace Ilex\Lib;

/**
 * Class Validate
 * A tool used to validate data efficiently.
 * @package Ilex\Lib
 * 
 * @property public static array $patterns
 *
 * @method public static array|boolean  batch(array &$values, array $rulePackages)
 * @method public static array|boolean  package(mixed &$value, array $rulePackage)
 * @method public static boolean|string rule(mixed &$value, string $ruleName, array $rule, string|boolean $message = FALSE)
 * 
 * @method public static boolean        type(mixed &$value, array $rule)
 * @method public static boolean        re(mixed $value, array $rule)
 * More methods ignored.
 * 
 * @method public static boolean        isInt(mixed $value)
 * @method public static boolean        isFloat(mixed $value)
 */
class Validate
{
    public static $patterns = [
        'alpha'     => '/^[\pL\pM]+$/u',
        'alpha_num' => '/^[\pL\pM\pN]+$/u',
        'aA'        => '/^[a-z]+$/i',
        'aA0'       => '/^[a-z0-9]+$/i',
        'chinese'   => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'captcha'   => '/^[a-z0-9]{4}$/i',
        'mobile'    => '/^1[3-9][0-9]{9}$/',
        'email'     => '/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i',
    ];

    /**
     * @param array $value
     * @param array $rulePackages
     * @return array|boolean
     */
    public static function batch(&$values, $rulePackages)
    {
        $errors = [];
        foreach ($rulePackages as $i => $rulePackage) {
            $name = isset($rulePackage['name']) ? $rulePackage['name'] : $i;

            /**
             * @todo: what?
             */
            if (isset($values[$name]) === FALSE) {
                if (isset($rulePackage['default'])) {
                    $values[$name] = $rulePackage['default'];
                } else {
                    if (isset($rulePackage['require'])) {
                        $errors[$name] = [$rulePackage['require']['message']];
                    }
                }
                continue;
            }

            $results = self::package($values[$name], $rulePackage);
            if ($results !== TRUE) {
                $errors[$name] = $results;
            }
        }
        return count($errors) ? $errors : TRUE;
    }

    /**
     * @param mixed $value
     * @param array $rulePackage
     * @return array|boolean
     */
    public static function package(&$value, $rulePackage)
    {
        $errors = [];
        foreach ($rulePackage as $ruleName => $rule) {
            if (in_array($ruleName, ['name', 'require', 'default'])) { // ignore some reserved names
                continue;
            } elseif ($ruleName === 'all') {
                foreach ($value as $valueItem) {
                    $result = self::package($valueItem, $rule);
                    if ($result !== TRUE) {
                        $errors += $result;
                    }
                }
            } else {
                $result = self::rule($value, $ruleName, $rule, $rule['message']);
                if ($result !== TRUE) {
                    $errors[] = $result;
                }
            }
        }
        return count($errors) ? $errors : TRUE;
    }

    /**
     * @param mixed          $value
     * @param string         $ruleName
     * @param array          $rule
     * @param string|boolean $message
     * @return boolean|string
     */
    public static function rule(&$value, $ruleName, $rule, $message = FALSE)
    {
        return self::$ruleName($value, $rule) ? TRUE : $message;
    }

    /*
     * ----------------------- -----------------------
     * Rules
     * ----------------------- -----------------------
     */

    /**
     * @param mixed $value
     * @param array $rule
     * @return boolean
     */
    public static function type(&$value, $rule)
    {
        switch ($rule['type']) {
            case 'int':
                if (self::is_int($value)) {
                    $value = intval($value); // Convert to int!
                    return TRUE;
                } else {
                    return FALSE;
                }
            case 'float':
                if (self::is_float($value)) {
                    $value = floatval($value); // Convert to float!
                    return TRUE;
                } else {
                    return FALSE;
                }
            case 'array':
                return is_array($value);
            default:
                throw new \Exception('Unrecognizable type "' . $rule['type'] . '" for Validation.');
        }
    }

    /**
     * @param mixed $value
     * @param array $rule
     * @return boolean
     */
    public static function re($value, $rule)
    {
        return preg_match(
            isset($rule['pattern']) ? $rule['pattern'] : self::$patterns[$rule['type']],
            $value
        ) === 1;
    }

    public static function eq($value, $rule)   { return $value ==  $rule['value']; }
    public static function ne($value, $rule)   { return $value !=  $rule['value']; }
    public static function same($value, $rule) { return $value === $rule['value']; }
    public static function diff($value, $rule) { return $value !== $rule['value']; }

    public static function        gt($value, $rule) { return          $value  >  $rule['value']; }
    public static function        lt($value, $rule) { return          $value  <  $rule['value']; }
    public static function        ge($value, $rule) { return          $value  >= $rule['value']; }
    public static function        le($value, $rule) { return          $value  <= $rule['value']; }

    public static function    int_gt($value, $rule) { return   intval($value) >  $rule['value']; }
    public static function    int_lt($value, $rule) { return   intval($value) <  $rule['value']; }
    public static function    int_ge($value, $rule) { return   intval($value) >= $rule['value']; }
    public static function    int_le($value, $rule) { return   intval($value) <= $rule['value']; }

    public static function  float_gt($value, $rule) { return floatval($value) >  $rule['value']; }
    public static function  float_lt($value, $rule) { return floatval($value) <  $rule['value']; }
    public static function  float_ge($value, $rule) { return floatval($value) >= $rule['value']; }
    public static function  float_le($value, $rule) { return floatval($value) <= $rule['value']; }

    public static function  count_gt($value, $rule) { return    count($value) >  $rule['value']; }
    public static function  count_lt($value, $rule) { return    count($value) <  $rule['value']; }
    public static function  count_ge($value, $rule) { return    count($value) >= $rule['value']; }
    public static function  count_le($value, $rule) { return    count($value) <= $rule['value']; }

    public static function length_gt($value, $rule) { return   strlen($value) >  $rule['value']; }
    public static function length_lt($value, $rule) { return   strlen($value) <  $rule['value']; }
    public static function length_ge($value, $rule) { return   strlen($value) >= $rule['value']; }
    public static function length_le($value, $rule) { return   strlen($value) <= $rule['value']; }
    public static function length_eq($value, $rule) { return   strlen($value) === $rule['value']; }

    public static function mb_length_gt($value, $rule) { return mb_strlen($value) >  $rule['value']; }
    public static function mb_length_lt($value, $rule) { return mb_strlen($value) <  $rule['value']; }
    public static function mb_length_ge($value, $rule) { return mb_strlen($value) >= $rule['value']; }
    public static function mb_length_le($value, $rule) { return mb_strlen($value) <= $rule['value']; }
    public static function mb_length_eq($value, $rule) { return mb_strlen($value) === $rule['value']; }

    /*
     * ----------------------- -----------------------
     * Kit
     * ----------------------- -----------------------
     */

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function isInt($value)
    {
        if (is_int($value)) {
            return TRUE;
        } elseif (preg_match('@^\d+$@', $value) === 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function isFloat($value)
    {
        if (is_float($value) OR is_int($value)) {
            return TRUE;
        } elseif (preg_match('@^(\d+(\.\d*)?|\.\d+)$@', $value) === 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}