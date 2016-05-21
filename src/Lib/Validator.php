<?php

namespace Ilex\Lib;

use \Ilex\Lib\Kit;
use ReflectionClass;

/**
 * Class Validator
 * A tool used to validate data efficiently.
 * @package Ilex\Lib
 * 
 * @property public array $patternList
 *
 * @method final public static boolean type(mixed &$value, array $rule)
 * @method final public static boolean re(mixed $value, array $rule)
 * More methods ignored.
 * 
 * @method final public static boolean isInt(mixed $value)
 * @method final public static boolean isFloat(mixed $value)
 * @method final public static boolean isDict(mixed $value)
 * @method final public static boolean isList(mixed $value)
 */
final class Validator
{
    // @todo: use public static variables or constants?s
    const 
        V_EXISTENCE = 'EXISTENCE',
        V_EXISTENCE_REQUIRE = 'EXISTENCE_REQUIRE',
        V_EXISTENCE_NOT_REQUIRE = 'EXISTENCE_NOT_REQUIRE',
        V_EXISTENCE_OPTIONAL = 'EXISTENCE_OPTIONAL',

        V_TYPE = 'TYPE',
        V_TYPE_NUMERIC = 'TYPE_NUMERIC',
        V_TYPE_INT = 'TYPE_INT',
        V_TYPE_FLOAT = 'TYPE_FLOAT',
        V_TYPE_STRING = 'TYPE_STRING',
        V_TYPE_BOOLEAN = 'TYPE_BOOLEAN',
        V_TYPE_ARRAY = 'TYPE_ARRAY',
        V_TYPE_LIST = 'TYPE_LIST',
        V_TYPE_DICT = 'TYPE_DICT',
        V_TYPE_OBJECTID = 'TYPE_OBJECTID',
        V_TYPE_DATETIME = 'TYPE_DATETIME',

        V_VALUE = 'VALUE',
        V_VALUE_AND = 'VALUE_AND',
        V_VALUE_OR = 'VALUE_OR',
        V_VALUE_NOT = 'VALUE_NOT',
        V_VALUE_E = 'VALUE_E',
        V_VALUE_NE = 'VALUE_NE',
        V_VALUE_LTE = 'VALUE_LTE',
        V_VALUE_GTE = 'VALUE_GTE',
        V_VALUE_LT = 'VALUE_LT',
        V_VALUE_GT = 'VALUE_GT',
        V_VALUE_LENGTH = 'VALUE_LENGTH',
        V_VALUE_REGEX = 'VALUE_REGEX',
        V_VALUE_ANY = 'VALUE_ANY',

        V_CAST = 'CAST',
        V_CAST_INT = 'CAST_TO_INT',
        V_CAST_FLOAT = 'CAST_TO_FLOAT',
        V_CAST_STRING = 'CAST_TO_STRING',
        V_CAST_BOOLEAN = 'CAST_TO_BOOLEAN',
        V_CAST_OBJECTID = 'CAST_TO_OBJECTID',
        V_CAST_DATETIME = 'CAST_TO_DATETIME',
        V_CAST_OPTIONAL_DROP = 'CAST_OPTIONAL_DROP',

        V_DEFAULT_VALUE = 'DEFAULT_VALUE',
    
        V_CHILDREN = 'CHILDREN',

        V_REST = 'REST',
        V_REST_REQUIRE = 'REST_REQUIRE',
        V_REST_NOT_REQUIRE = 'REST_NOT_REQUIRE',

        V_VERSION = 'VERSION',
        V_VERSION_DEFAULT = 'VERSION_DEFAULT';

    private static $patternTagNameList;
    private static $patternTagValueList;

    private static $constantList = [
        V_EXISTENCE, V_EXISTENCE_REQUIRE, V_EXISTENCE_NOT_REQUIRE, V_EXISTENCE_OPTIONAL, 
        V_TYPE, V_TYPE_NUMERIC, V_TYPE_INT, V_TYPE_FLOAT, V_TYPE_STRING, V_TYPE_BOOLEAN, V_TYPE_ARRAY, V_TYPE_LIST, V_TYPE_DICT, V_TYPE_OBJECTID, V_TYPE_DATETIME, 
        V_VALUE, V_VALUE_AND, V_VALUE_OR, V_VALUE_NOT, V_VALUE_E, V_VALUE_NE, V_VALUE_LTE, V_VALUE_GTE, V_VALUE_LT, V_VALUE_GT, V_VALUE_LENGTH, V_VALUE_REGEX, V_VALUE_ANY, 
        V_CAST, V_CAST_INT, V_CAST_FLOAT, V_CAST_STRING, V_CAST_BOOLEAN, V_CAST_OBJECTID, V_CAST_DATETIME, V_CAST_OPTIONAL_DROP, 
        V_DEFAULT_VALUE, 
        V_CHILDREN, 
        V_REST, V_REST_REQUIRE, V_REST_NOT_REQUIRE, 
        V_VERSION, V_VERSION_DEFAULT,
    ];

    /**
     * 
     * check all array keys and values in $pattern being consts in this class
     * check all array values in $pattern using several 'if'. DO NOT use 'else'
     * @param 
     * @return 
     */
    final public static function validate($pattern, $data)
    {
        if (TRUE === is_null(self::$patternTagNameList)) {

        }
        if (TRUE === is_null(self::$patternTagValueList)) {
            
        }

        if (count($unknown_tag_list = array_diff(array_keys($pattern), self::$constantList)) > 0)
            return Kit::generateErrorInfo('Unknown pattern tag found.', $unknown_tag_list);
        return TRUE;
    }

 












    private static $patternList = [
        'aA'        => '/^[a-z]+$/i',
        'aA0'       => '/^[a-z0-9]+$/i',
        'alpha'     => '/^[\pL\pM]+$/u',
        'alpha_num' => '/^[\pL\pM\pN]+$/u',
        'captcha'   => '/^[a-z0-9]{4}$/i',
        'chinese'   => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'email'     => '/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i',
        'mobile'    => '/^1[3-9][0-9]{9}$/',
    ];

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
    final public static function type(&$value, $rule)
    {
        switch ($rule['type']) {
            case 'int':
                if (TRUE === self::is_int($value)) {
                    $value = intval($value); // Convert to int!
                    return TRUE;
                } else {
                    return FALSE;
                }
            case 'float':
                if (TRUE === self::is_float($value)) {
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
    final public static function re($value, $rule)
    {
        return 1 === preg_match(
            TRUE === isset($rule['pattern']) ? $rule['pattern'] : self::$patternList[$rule['type']],
            $value
        );
    }

    final public static function           eq($value, $rule) { return           $value  ==  $rule['value']; }
    final public static function           ne($value, $rule) { return           $value  !=  $rule['value']; }
    final public static function         same($value, $rule) { return           $value  === $rule['value']; }
    final public static function         diff($value, $rule) { return           $value  !== $rule['value']; }

    final public static function           gt($value, $rule) { return           $value  >   $rule['value']; }
    final public static function           lt($value, $rule) { return           $value  <   $rule['value']; }
    final public static function           ge($value, $rule) { return           $value  >=  $rule['value']; }
    final public static function           le($value, $rule) { return           $value  <=  $rule['value']; }

    final public static function       int_gt($value, $rule) { return    intval($value) >   $rule['value']; }
    final public static function       int_lt($value, $rule) { return    intval($value) <   $rule['value']; }
    final public static function       int_ge($value, $rule) { return    intval($value) >=  $rule['value']; }
    final public static function       int_le($value, $rule) { return    intval($value) <=  $rule['value']; }

    final public static function     float_gt($value, $rule) { return  floatval($value) >   $rule['value']; }
    final public static function     float_lt($value, $rule) { return  floatval($value) <   $rule['value']; }
    final public static function     float_ge($value, $rule) { return  floatval($value) >=  $rule['value']; }
    final public static function     float_le($value, $rule) { return  floatval($value) <=  $rule['value']; }

    final public static function     count_gt($value, $rule) { return     count($value) >   $rule['value']; }
    final public static function     count_lt($value, $rule) { return     count($value) <   $rule['value']; }
    final public static function     count_ge($value, $rule) { return     count($value) >=  $rule['value']; }
    final public static function     count_le($value, $rule) { return     count($value) <=  $rule['value']; }

    final public static function    length_gt($value, $rule) { return    strlen($value) >   $rule['value']; }
    final public static function    length_lt($value, $rule) { return    strlen($value) <   $rule['value']; }
    final public static function    length_ge($value, $rule) { return    strlen($value) >=  $rule['value']; }
    final public static function    length_le($value, $rule) { return    strlen($value) <=  $rule['value']; }
    final public static function    length_eq($value, $rule) { return    strlen($value) === $rule['value']; }

    final public static function mb_length_gt($value, $rule) { return mb_strlen($value) >   $rule['value']; }
    final public static function mb_length_lt($value, $rule) { return mb_strlen($value) <   $rule['value']; }
    final public static function mb_length_ge($value, $rule) { return mb_strlen($value) >=  $rule['value']; }
    final public static function mb_length_le($value, $rule) { return mb_strlen($value) <=  $rule['value']; }
    final public static function mb_length_eq($value, $rule) { return mb_strlen($value) === $rule['value']; }

    /*
     * ----------------------- -----------------------
     * Kit
     * ----------------------- -----------------------
     */

    /**
     * @param mixed $value
     * @return boolean
     */
    final public static function isInt($value)
    {
        if (TRUE === is_int($value)) {
            return TRUE;
        } elseif (1 === preg_match('@^\d+$@', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    final public static function isFloat($value)
    {
        if (TRUE === is_float($value) OR TRUE === is_int($value)) {
            return TRUE;
        } elseif (1 === preg_match('@^(\d+(\.\d*)?|\.\d+)$@', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks whether a value is a dict.
     * @param mixed $value
     * @return boolean
     */
    final public static function isDict($value)
    {
        if (FALSE === is_array($value)) return FALSE;
        if (0 === count($value)) return TRUE;
        return FALSE === self::isList($value);
    }

    /**
     * Checks whether a value is a list.
     * @param mixed $value
     * @return boolean
     */
    final public static function isList($value)
    {
        if (FALSE === is_array($value)) return FALSE;
        if (0 === count($value)) return TRUE;
        return array_keys($value) === range(0, count($value) - 1);
    }
}