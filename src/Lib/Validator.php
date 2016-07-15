<?php

namespace Ilex\Lib;

use \ReflectionClass;
use \Ilex\Lib\UserException;

/**
 * @todo: method arg type validate
 * Class Validator
 * A tool used to validate data efficiently.
 * @package Ilex\Lib
 * 
 * @property public array $patternList
 *
 * @method final public static boolean isInt(mixed $value)
 * @method final public static boolean isFloat(mixed $value)
 */
final class Validator
{
    // @todo: use public static variables or constants?
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

    // private static $constantList = [
    //     V_EXISTENCE, V_EXISTENCE_REQUIRE, V_EXISTENCE_NOT_REQUIRE, V_EXISTENCE_OPTIONAL, 
    //     V_TYPE, V_TYPE_NUMERIC, V_TYPE_INT, V_TYPE_FLOAT, V_TYPE_STRING, V_TYPE_BOOLEAN, V_TYPE_ARRAY, V_TYPE_LIST, V_TYPE_DICT, V_TYPE_OBJECTID, V_TYPE_DATETIME, 
    //     V_VALUE, V_VALUE_AND, V_VALUE_OR, V_VALUE_NOT, V_VALUE_E, V_VALUE_NE, V_VALUE_LTE, V_VALUE_GTE, V_VALUE_LT, V_VALUE_GT, V_VALUE_LENGTH, V_VALUE_REGEX, V_VALUE_ANY, 
    //     V_CAST, V_CAST_INT, V_CAST_FLOAT, V_CAST_STRING, V_CAST_BOOLEAN, V_CAST_OBJECTID, V_CAST_DATETIME, V_CAST_OPTIONAL_DROP, 
    //     V_DEFAULT_VALUE, 
    //     V_CHILDREN, 
    //     V_REST, V_REST_REQUIRE, V_REST_NOT_REQUIRE, 
    //     V_VERSION, V_VERSION_DEFAULT,
    // ];

    /**
     * 
     * check all array keys and values in $pattern being consts in this class
     * check all array values in $pattern using several 'if'. DO NOT use 'else'
     * @param 
     * @return boolean
     * @throws UserException if there is unknown pattern tag found.
     */
    final public static function validate($pattern, $data)
    {
        if (TRUE === is_null(self::$patternTagNameList))
            self::$patternTagNameList = array_keys((new ReflectionClass(get_class()))->getConstants());
        if (TRUE === is_null(self::$patternTagValueList))
            self::$patternTagValueList = array_values((new ReflectionClass(get_class()))->getConstants());
        $tag_list = array_merge(self::$patternTagNameList, self::$patternTagValueList);
        if (Kit::len($unknown_tag_list = array_diff(array_keys($pattern), $tag_list)) > 0)
            throw new UserException('Unknown pattern tag found.', $unknown_tag_list);
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


    /**
     * @param mixed $value
     * @return boolean
     */
    final public static function isInt($value)
    {
        if (TRUE === Kit::isInt($value)) {
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
        if (TRUE === Kit::isFloat($value) OR TRUE === Kit::isInt($value)) {
            return TRUE;
        } elseif (1 === preg_match('@^(\d+(\.\d*)?|\.\d+)$@', $value)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}