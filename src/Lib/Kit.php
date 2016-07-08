<?php

namespace Ilex\Lib;

use \Closure;
use \Ilex\Lib\UserException;
use \Ilex\Lib\UserTypeException;

/**
 * @todo: method arg type validate
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 *
 * @method final public static array      columns(array $list_of_array, array|mixed $column_name_list
 *                                            , boolean $ensure_existence = TRUE, mixed $default = NULL
 *                                            , boolean $return_only_values = FALSE)
 * @method final public static array      columnsExclude(array $list_of_array, array $column_name_list
 *                                            , boolean $check_existence = FALSE)
 * @method final public static array      exclude(array $array, array $key_list
 *                                            , boolean $check_existence = FALSE)
 * @method final public static array      extract(array $array, array $key_list
 *                                            , boolean $ensure_existence = TRUE, mixed $default = NULL)
 * @method final public static string     getRealPath(string $path)
 * @method final public static string     j(mixed $data)
 * @method final public static mixed|NULL last(array $array, int $offset = 1)
 * @method final public array|FALSE       randomByWeight(array $list_of_dict)
 * @method final public static array      separateTitleWords(string $string)
 * @method final public static string     time(int|NULL $time = NULL, string $format = 'Y-m-d H:i:s')
 * @method final public static string     toString(mixed $data, boolean $quotation_mark_list = TRUE)
 * @method final public static string     type(mixed $variable, string $empty_array = 'list')
 */
final class Kit
{

    const TYPE_VACANCY  = '@[VACANCY]#';
    const TYPE_STRING   = 'STRING';
    const TYPE_INT      = 'INT';
    const TYPE_FLOAT    = 'FLOAT';
    const TYPE_BOOLEAN  = 'BOOLEAN';
    const TYPE_ARRAY    = 'ARRAY';
    const TYPE_LIST     = 'LIST';
    const TYPE_DICT     = 'DICT';
    const TYPE_OBJECT   = 'OBJECT';
    const TYPE_RESOURCE = 'RESOURCE';
    const TYPE_NULL     = 'NULL';

    const M_MIN = 'min';
    const M_MAX = 'max';

    // ================================================== //
    //                        Type                        //
    // ================================================== //

    /**
     * Gets the type of the given variable.
     * @param mixed $variable
     * @param string $variable
     * @return string
     * @throws UserException if $empty_array is invalid or type of $variable unknown.
     */
    final public static function type(&$variable, $distinguish_array = FALSE, $empty_array = self::TYPE_LIST)
    {
        if (self::TYPE_LIST !== $empty_array AND self::TYPE_DICT !== $empty_array)
            throw new UserException('Invalid $empty_array.', $empty_array);
        if (FALSE === $distinguish_array) {
            if (is_array($variable)) return self::TYPE_ARRAY;
        } elseif (TRUE === is_array($variable)) {
            if (0 === count($variable)) return $empty_array;
            foreach (array_keys($variable) as $key => $value) {
                if ($key !== $value) return self::TYPE_DICT; 
            }
            return self::TYPE_LIST;
        }
        if (TRUE === is_string($variable))   return self::TYPE_STRING; 
        if (TRUE === is_int($variable))      return self::TYPE_INT; 
        if (TRUE === is_float($variable))    return self::TYPE_FLOAT; 
        if (TRUE === is_bool($variable))     return self::TYPE_BOOLEAN; 
        if (TRUE === is_object($variable))   return self::TYPE_OBJECT; 
        if (TRUE === is_resource($variable)) return self::TYPE_RESOURCE; 
        if (TRUE === is_null($variable))     return self::TYPE_NULL; 
        throw new UserException('Unknown type of $variable given.', $variable);
    }

    final public static function isValidType($type)
    {
        if (TRUE === is_null($type)) return FALSE;
        return TRUE === in_array($type, [
            self::TYPE_STRING,
            self::TYPE_INT,
            self::TYPE_FLOAT,
            self::TYPE_BOOLEAN,
            self::TYPE_ARRAY,
            self::TYPE_LIST,
            self::TYPE_DICT,
            self::TYPE_OBJECT,
            self::TYPE_RESOURCE,
            self::TYPE_NULL,
        ], TRUE);
    }

    final public static function isType(&$variable, $type_list, $can_be_null = FALSE)
    {
        if (FALSE === is_bool($can_be_null))
            throw new UserTypeException($can_be_null, self::TYPE_BOOLEAN);
        if (FALSE === $can_be_null AND FALSE === is_array($type_list)) {
            switch ($type_list) {
                case self::TYPE_STRING:
                    return TRUE === is_string($variable);
                    break;
                case self::TYPE_INT:
                    return TRUE === is_int($variable);
                    break;
                case self::TYPE_FLOAT:
                    return TRUE === is_float($variable);
                    break;
                case self::TYPE_BOOLEAN:
                    return TRUE === is_bool($variable);
                    break;
                case self::TYPE_ARRAY:
                    return TRUE === is_array($variable);
                    break;
                case self::TYPE_NULL:
                    return TRUE === is_null($variable);
                    break;
                case self::TYPE_OBJECT:
                    return TRUE === is_object($variable);
                    break;
                case self::TYPE_RESOURCE:
                    return TRUE === is_resource($variable);
                    break;
            }
        }
        if (FALSE === is_array($type_list)) $type_list = [ $type_list ];
        if (TRUE === $can_be_null AND FALSE === in_array(self::TYPE_NULL, $type_list, TRUE)) {
            $type_list[] = self::TYPE_NULL;
        }
        $distinguish_array = FALSE;
        $empty_array       = self::TYPE_LIST;
        if (FALSE === in_array(self::TYPE_ARRAY, $type_list, TRUE) 
            AND (TRUE === in_array(self::TYPE_LIST, $type_list, TRUE) 
                OR TRUE === in_array(self::TYPE_DICT, $type_list, TRUE))) {
            $distinguish_array = TRUE;
            if (TRUE === in_array(self::TYPE_DICT, $type_list, TRUE)) $empty_array = self::TYPE_DICT;
            else $empty_array = self::TYPE_LIST;
        }
        return TRUE === in_array(self::type($variable, $distinguish_array, $empty_array), $type_list, TRUE);
    }

    final public static function ensureType(&$variable, $type_list, $can_be_null = FALSE)
    {
        if (FALSE === self::isType($variable, $type_list, $can_be_null))
            throw new UserTypeException($variable, $type_list);
    }

    final public static function isList(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_LIST, $can_be_null);
    }

    final public static function ensureList(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_LIST, $can_be_null);
    }

    final public static function isListOfType(&$list, $type_list)
    {
        self::ensureList($list);
        foreach ($list as $value) {
            if (FALSE === self::isType($value, $type_list)) return FALSE;
        }
        return TRUE;
    }

    final public static function ensureListOfType(&$list, $type_list)
    {
        if (FALSE === self::isListOfType($list, $type_list))
            throw new UserException('Values in $list is not of $type_list.', [ $list, $type_list ]);
    }

    final public static function isListOfList(&$list)
    {
        return self::isListOfType($list, self::TYPE_LIST);
    }

    final public static function ensureListOfList(&$list)
    {
        return self::ensureListOfType($list, self::TYPE_LIST);
    }

    final public static function isListOfDict(&$list)
    {
        return self::isListOfType($list, self::TYPE_DICT);
    }

    final public static function ensureListOfDict(&$list)
    {
        return self::ensureListOfType($list, self::TYPE_DICT);
    }

    final public static function isListOfArray(&$list)
    {
        return self::isListOfType($list, self::TYPE_ARRAY);
    }

    final public static function ensureListOfArray(&$list)
    {
        return self::ensureListOfType($list, self::TYPE_ARRAY);
    }

    final public static function isMatrix(&$matrix)
    {
        if (FALSE === self::isList($matrix) OR FALSE === self::isListOfList($matrix)) return FALSE;
        if (FALSE === self::isAllValuesSame(array_map('count', $matrix))) return FALSE;
        return TRUE;
    }

    final public static function ensureMatrix(&$matrix)
    {
        if (FALSE === self::isMatrix($list))
            throw new UserException('$matrix is not a matrix.', $matrix);
    }

    final public static function isDict(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_DICT, $can_be_null);
    }

    final public static function ensureDict(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_DICT, $can_be_null);
    }

    final public static function isDictOfType(&$dict, $type_list)
    {
        self::ensureDict($dict);
        foreach ($dict as $value) {
            if (FALSE === self::isType($value, $type_list)) return FALSE;
        }
        return TRUE;
    }

    final public static function ensureDictOfType(&$dict, $type_list)
    {
        if (FALSE === self::isDictOfType($dict, $type_list))
            throw new UserException('Values in $dict is not of $type_list.', [ $dict, $type_list ]);
    }

    final public static function isDictOfList(&$dict)
    {
        return self::isDictOfType($list, self::TYPE_LIST);
    }

    final public static function ensureDictOfList(&$dict)
    {
        return self::ensureDictOfType($list, self::TYPE_LIST);
    }

    final public static function isDictOfDict(&$dict)
    {
        return self::isDictOfType($list, self::TYPE_DICT);
    }

    final public static function ensureDictOfDict(&$dict)
    {
        return self::ensureDictOfType($list, self::TYPE_DICT);
    }

    final public static function isDictOfArray(&$dict)
    {
        return self::isDictOfType($dict, self::TYPE_ARRAY);
    }

    final public static function ensureDictOfArray(&$dict)
    {
        return self::ensureDictOfType($dict, self::TYPE_ARRAY);
    }

    final public static function isArray(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_ARRAY, $can_be_null);
    }

    final public static function ensureArray(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_ARRAY, $can_be_null);
    }

    final public static function isString(&$variable, $can_be_null = FALSE, $can_be_empty = FALSE)
    {
        self::ensureBoolean($can_be_empty);
        return self::isType($variable, self::TYPE_STRING, $can_be_null)
            AND (TRUE === $can_be_null OR TRUE === $can_be_empty OR '' !== $variable);
    }

    final public static function ensureString(&$variable, $can_be_null = FALSE, $can_be_empty = FALSE)
    {
        if (FALSE === self::isString($variable, $can_be_null, $can_be_empty))
            throw new UserTypeException($variable, self::TYPE_STRING);
    }

    final public static function isMatchRegex(&$variable, $regex, $can_be_null = FALSE)
    {
        self::ensureString($variable, $can_be_null);
        if (TRUE === is_null($variable))
            if (TRUE === $can_be_null) return TRUE;
            else throw new UserException('$variable can not be NULL.');
        $result = preg_match('/' . $regex . '/', $variable);
        if (FALSE === $result)
            throw new UserException('Regex match failed.', preg_last_error());
        return (1 === $result);
    }

    final public static function ensureMatchRegex(&$variable, $regex, $can_be_null = FALSE)
    {
        if (FALSE === self::isMatchRegex($variable, $regex, $can_be_null))
            throw new UserException("\$variable($variable) match \$regex($regex) failed.", $can_be_null);
    }

    final public static function isInt(&$variable, $can_be_null = FALSE, $should_be_positive = TRUE)
    {
        self::ensureBoolean($should_be_positive);
        return self::isType($variable, self::TYPE_INT, $can_be_null)
            AND (TRUE === $can_be_null OR FALSE === $should_be_positive OR $variable > 0);
    }

    final public static function ensureInt(&$variable, $can_be_null = FALSE, $should_be_positive = TRUE)
    {
        if (FALSE === self::isInt($variable, $can_be_null, $should_be_positive))
            throw new UserTypeException($variable, self::TYPE_INT);
    }

    final public static function isFloat(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_FLOAT, $can_be_null);
    }

    final public static function ensureFloat(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_FLOAT, $can_be_null);
    }

    final public static function isBoolean(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_BOOLEAN, $can_be_null);
    }

    final public static function ensureBoolean(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_BOOLEAN, $can_be_null);
    }

    final public static function isObject(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_OBJECT, $can_be_null);
    }

    final public static function ensureObject(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_OBJECT, $can_be_null);
    }

    final public static function isResource(&$variable, $can_be_null = FALSE)
    {
        return self::isType($variable, self::TYPE_RESOURCE, $can_be_null);
    }

    final public static function ensureResource(&$variable, $can_be_null = FALSE)
    {
        self::ensureType($variable, self::TYPE_RESOURCE, $can_be_null);
    }

    final public static function isNULL(&$variable)
    {
        return self::isType($variable, self::TYPE_NULL);
    }

    final public static function ensureNULL(&$variable)
    {
        self::ensureType($variable, self::TYPE_NULL);
    }

    final public static function isVacancy(&$variable)
    {
        return self::TYPE_VACANCY === $variable;
    }

    final public static function ensureVacancy(&$variable)
    {
        if (FALSE === self::isVacancy($variable))
            throw new UserException('$variable is not vacancy.', $variable);
            
    }

    // ================================================== //
    //                       Mixed                        //
    // ================================================== //


    final public static function len(&$string_or_array)
    {
        self::ensureType($string_or_array, [ self::TYPE_STRING, self::TYPE_ARRAY ]);
        if (TRUE === self::isString($string_or_array, FALSE, TRUE)) {
            // strlen() returns the number of bytes rather than the number of characters in a string.
            return strlen($string_or_array);
        } else {
            self::ensureArray($string_or_array);
            return count($string_or_array);
        }
    }

    // ================================================== //
    //                       String                       //
    // ================================================== //

    final public static function toInt(&$string)
    {
        self::ensureMatchRegex($string, '^\d{1,9}$');
        return intval($string);
    }

    final public static function join($delimiter, &$string_list)
    {
        self::ensureString($delimiter, FALSE, TRUE);
        self::ensureListOfType($string_list, self::TYPE_STRING);
        return implode($delimiter, $string_list);
    }

    final public static function split($delimiter, &$string)
    {
        self::ensureString($delimiter);
        self::ensureString($string);
        if ('' === $delimiter)
            throw new UserException("$delimiter is an empty string when splitting string($string).", $string);
        return explode($delimiter, $string);
    }

    // final public static function lstrip()
    // final public static function strip()
    // final public static function rstrip()
    // final public static function toUpper()
    // final public static function isUpper()
    // final public static function toLower()
    // final public static function isLower()
    // final public static function toTitle()
    // final public static function isTitle()
    // final public static function swapCase()
    // final public static function strpos()

    /**
     * Separates title words in a string.
     * @param string $string
     * @return array
     */
    final public static function separateTitleWords($string)
    {
        self::ensureString($string);
        $match_list = [];
        preg_match_all('/[A-Z][a-z]*/', $string, $match_list, PREG_OFFSET_CAPTURE);
        $match_list = $match_list[0];
        if (self::len($match_list) > 0) {
            $result = [];
            // @todo: add comment to this logic?
            if ($match_list[0][1] > 0) $result[] = substr($string, 0, $match_list[0][1]);
            foreach ($match_list as $match) $result[] = $match[0];
            return $result;
        } else return [ $string ];
    }

    // ================================================== //
    //                       Array                        //
    // ================================================== //

    final public static function count(&$value, &$array)
    {
        self::ensureArray($array);
        return count(array_keys($array, $value, TRUE));
    }

    final public static function isEmpty(&$array)
    {
        self::ensureArray($array);
        return 0 === count($array);
    }

    final public static function in($value, $array)
    {
        self::ensureArray($array);
        return TRUE === in_array($value, $array, TRUE);
    }

    final public static function ensureIn(&$value, $array)
    {
        if (FALSE === self::in($value, $array)) {
            $msg = "\$value(" . self::toString($value) . ") is not in \$array(" . self::toString($array) . ").";
            throw new UserException($msg, [ $value, $array ]);
        }
    }

    final public static function hasKey()
    {
        $arg_list = func_get_args();
        $array = $arg_list[0];
        self::ensureArray($dict);
        $key_list = array_slice($arg_list, 1);
        $now = $array;
        foreach ($key_list as $key) {
            if (FALSE === self::isArray($now)) return FALSE;
            if (FALSE === array_key_exists($key, $now)) return FALSE;
            $now = $now[$key];
        }
        return TRUE;
    }

    final public static function ensureHasKey()
    {
        $arg_list = func_get_args();
        if (FALSE === call_user_func_array([ self, 'hasKey' ], $arg_list))
            throw new UserException('$array has no $key.', $arg_list);
    }

    final public static function issetKey()
    {
        $arg_list = func_get_args();
        $array = $arg_list[0];
        self::ensureArray($array);
        $key_list = array_slice($arg_list, 1);
        $now = $array;
        foreach ($key_list as $key) {
            if (FALSE === self::isArray($now)) return FALSE;
            if (FALSE === isset($key, $now)) return FALSE;
            $now = $now[$key];
        }
        return TRUE;
    }

    final public static function ensureIssetKey()
    {
        $arg_list = func_get_args();
        if (FALSE === call_user_func_array([ self, 'issetKey' ], $arg_list))
            throw new UserException('$key is not set in $array.', $arg_list);
    }

    final public static function getValue()
    {
        $arg_list = func_get_args();
        $array = $arg_list[0];
        self::ensureArray($array);
        $key_list = array_slice($arg_list, 1);
        $now = $array;
        foreach ($key_list as $key) {
            if (FALSE === self::isArray($now)) return NULL;
            if (FALSE === isset($key, $now)) return NULL;
            $now = $now[$key];
        }
        return $now;
    }

    /**
     * Extracts fields in an array.
     * @param array       $array
     * @param array|mixed $key_list    a list of field keys
     * @param boolean     $set_default whether it needs to set default value for empty fields
     * @param mixed       $default     default value to be set for empty fields
     * @return array
     * @throws UserException if field($field_name) is empty
     */
    final public static function extract(&$array, $key_list, $ensure_existence = TRUE, $default = NULL)
    {
        self::ensureArray($array);
        if (FALSE === is_array($key_list))
            $key_list = [ $key_list ];
        self::ensureListOfType($key_list, [ self::TYPE_STRING, self::TYPE_INT ]);
        $result = [];
        foreach ($key_list as $key) {
            if (TRUE === isset($array[$key])) {
                $result[$key] = $array[$key];
            } elseif (FALSE === $ensure_existence) {
                $result[$key] = $default;
            } else {
                $msg = "Field($key) is empty, thus can not be included.";
                throw new UserException($msg, $array);
            }
        }
        return $result;
    }

    /**
     * Extracts fields in an array by excluding specific fields.
     * @param array       $array
     * @param array|mixed $key_list        a list of field keys
     * @param boolean     $check_existence whether it will check the existence of the to-be-excluded field
     * @return array
     */
    final public static function exclude($array, $key_list, $check_existence = FALSE)
    {
        self::ensureArray($array);
        if (FALSE === is_array($key_list))
            $key_list = [ $key_list ];
        self::ensureListOfType($key_list, [ self::TYPE_STRING, self::TYPE_INT ]);
        $result = $array;
        foreach ($key_list as $key) {
            if (FALSE === isset($array[$key]) AND TRUE === $check_existence) {
                $msg = "Field($key) does not exist, thus can not be excluded.";
                throw new UserException($msg, $array);
            }
            unset($result[$key]);
        }
        return $result;
    }

    final public static function isAllSame(&$array, $value = self::TYPE_VACANCY)
    {
        self::ensureArray($array);
        $value_list = array_values($array);
        if (0 === count($value_list)) return TRUE;
        if (FALSE === Kit::isVacancy($value) AND $value_list[0] !== $value) return FALSE;
        if (self::count($value_list[0], $value_list) !== self::len($value_list)) return FALSE;
        return TRUE;
    }

    final public static function ensureAllSame(&$array, $value = self::TYPE_VACANCY)
    {
        if (FALSE === self::isAllSame($array, $value))
            throw new UserException('Values in $array are not same (or $value).', [ $array, $value ]);
    }

    final public static function sum(&$array)
    {
        self::ensureListOfType($array, [ self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_BOOLEAN ]);
        return array_sum($array);
    }

    // ================================================== //
    //                       List                         //
    // ================================================== //

    final public static function slice(&$list, $offset, $length = NULL)
    {
        self::ensureList($list);
        self::ensureInt($offset, FALSE, FALSE);
        self::ensureInt($length, TRUE);
        // @TODO: check corner cases
        return array_slice($list, $offset, $length);
    }

    /**
     * Returns the last $offset element in $list.
     * @param array $list
     * @param int   $offset
     * @return mixed|NULL
     * @throws UserException if $offset overflows $list.
     */
    final public static function last(&$list, $offset = 1)
    {
        self::ensureList($list);
        self::ensureInt($offset);
        if ($offset > self::len($list))
            throw new UserException("\$offset($offset) overflows $list.", $list);
        return self::slice($list, - $offset)[0];
    }

    final public static function mapped($function, &$list)
    {
        self::ensureArray($list);
        $arg_list = func_get_args();
        if (count($arg_list) > 2) $arg_list = Kit::slice($arg_list, 2); else $arg_list = [];
        $result = [];
        foreach ($list as $index => $item) {
            $result[] = call_user_func_array($function, array_merge([ $item ], $arg_list, [ $index ]));
        }
        return $result;
    }

    final public static function map($function, &$list)
    {
        $list = call_user_func_array([ 'self', 'mapped' ], func_get_args());
        return $list;
    }

    // final public static function sort(&$list)
    // final public static function sorted(&$list)
    // final public static function remove(&$list)
    // final public static function removed(&$list)
    
    final public static function popedList(&$list)
    {
        self::ensureList($list);
        if (0 === count($list))
            throw new UserException('Can not pop from an empty list.');
        return Kit::slice($list, 0, count($list) - 1);
    }

    final public static function popList(&$list)
    {
        self::ensureList($list);
        if (0 === count($list))
            throw new UserException('Can not pop from an empty list.');
        return array_pop($list);
    }

    // final public static function insert(&$list)
    // final public static function inserted(&$list)
    // final public static function index(&$list)
    
    final public static function extended(&$list, &$value_list)
    {
        self::ensureList($list);
        self::ensureList($value_list);
        return array_merge($list, $value_list);
    }

    final public static function extend(&$list, &$value_list)
    {
        $list = self::extended($list, $value_list);
        return count($list);
    }

    final public static function reversed(&$list)
    {
        self::ensureList($list);
        return array_reverse($list);
    }

    final public static function reverse(&$list)
    {
        $list = self::reversed($list);
        return count($list);
    }

    // ================================================== //
    //                       Dict                         //
    // ================================================== //

    final public static function keys(&$dict)
    {
        self::ensureDict($dict);
        return array_keys($dict);
    }

    final public static function values(&$dict)
    {
        self::ensureDict($dict);
        return array_values($dict);
    }

    final public static function items(&$dict)
    {
        self::ensureDict($dict);
        $result = [];
        foreach ($dict as $key => $value) {
            $result[] = [ $key, $value ];
        }
        return $result;
    }

    /**
     * @param array $key_list
     * @param array $value_list
     * @return array
     */
    final public static function dict(&$key_list, &$value_list)
    {
        self::ensureListOfType($key_list, [ self::TYPE_STRING, self::TYPE_INT ]);
        self::ensureList($value_list);
        if (count($key_list) !== count($value_list))
            throw new UserException('Length of $key_list and $value_list do not equal.', [ $key_list, $value_list ]);
        if (TRUE === self::isEmpty($key_list)) return [];
        return array_combine($key_list, $value_list);
    }

    // final public static function popDict(&$dict, &$key)
    // final public static function popedDict(&$dict, &$key)
    // final public static function popItem(&$dict)
    final public static function update(&$dict1, &$dict2)
    {
        self::ensureDict($dict1);
        self::ensureDict($dict2);
        $dict1 = array_merge($dict1, $dict2);
    }

    final public static function updated(&$dict1, &$dict2)
    {
        self::ensureDict($dict1);
        self::ensureDict($dict2);
        return array_merge($dict1, $dict2);
    }


    // ================================================== //
    //                    ArrayOfArray                    //
    // ================================================== //

    /**
     * Extracts columns in a list_of_array.
     * @param array       $list_of_array
     * @param array|mixed $column_name_list   a list of column names or a column name
     * @param boolean     $ensure_existence   whether the field needs to exist
     * @param mixed       $default            default value to be set for empty fields
     * @param boolean     $return_only_values whether it should return only values
     *                                        when there is only one column to return
     * @return array
     * @throws UserException if field($column_name) is empty, or attempting return only values
     *                       when the length of $column_name_list is not 1.
     */
    final public static function columns(&$list_of_array, $column_name_list, $ensure_existence = TRUE
        , $default = NULL, $return_only_values = FALSE)
    {
        self::ensureListOfArray($list_of_array);
        self::ensureBoolean($ensure_existence);
        self::ensureBoolean($return_only_values);
        if (FALSE === is_array($column_name_list))
            $column_name_list = [ $column_name_list ];
        $result = [];
        foreach ($list_of_array as $raw_array) {
            $array = self::extract($raw_array, $column_name_list, $ensure_existence, $default);
            if (TRUE === $return_only_values) {
                if (1 === count($column_name_list)) {
                    $array = $array[$column_name_list[0]];
                } else {
                    $msg  = 'Can not return only values, '
                        . 'because the length of $column_name_list is not 1.';
                    throw new UserException($msg, $column_name_list);
                }
            }
            $result[] = $array;
        }
        return $result;
    }

    /**
     * Extracts columns in a list_of_array by excluding specific fields.
     * @param array       $list_of_array
     * @param array|mixed $column_name_list a list of column names or a column name
     * @param boolean     $check_existence  whether it will check the existence of the to-be-excluded field
     * @return array
     */
    final public static function columnsExclude(&$list_of_array, $column_name_list, $check_existence = FALSE)
    {
        self::ensureListOfArray($list_of_array);
        self::ensureBoolean($check_existence);
        if (FALSE === is_array($column_name_list))
            $column_name_list = [ $column_name_list ];
        $result = [];
        foreach ($list_of_array as $raw_array) {
            $result[] = self::exclude($raw_array, $column_name_list, $check_existence);
        }
        return $result;
    }

    // ================================================== //
    //                        Data                        //
    // ================================================== //

    /**
     * Generates the string form of data.
     * @param mixed $data
     * @return string
     */
    final public static function toString(&$data)
    {
        if (TRUE === self::isNULL($data)) return 'NULL';
        if (TRUE === self::isType($data, [ self::TYPE_INT, self::TYPE_FLOAT ])) return strval($data);
        if (TRUE === self::isString($data, FALSE, TRUE)) return $data;
        throw new UserException('Unknown type of $data.', $data);
    }
    
    /**
     * Prettily prints data.
     * @param mixed $data
     * @return string
     */
    final public static function j(&$data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    // ================================================== //
    //                     Date & Time                    //
    // ================================================== //

    /**
     * Converts time to date string and returns it.
     * @param int|NULL $time
     * @param string   $format
     * @return string
     */
    final public static function time($time = NULL, $format = 'Y-m-d H:i:s')
    {
        if (TRUE === Kit::isNULL($time)) $time = time();
        return date($format, $time);
    }

    // ================================================== //
    //                        Path                        //
    // ================================================== //

    /**
     * Returns canonicalized absolute pathname with a trailing '/'.
     * eg. '/home/user/Project/Test/../Test/app' => '/home/user/Project/Test/app/'
     * @param string $path
     * @return string
     */
    final public static function getRealPath($path)
    {
        if (FALSE !== ($realpath = realpath($path)))
            $path = $realpath . '/';
        else $path = rtrim($path, '/') . '/'; // @todo: change function to Kit::rstrip()
        return $path;
    }

    // ================================================== //
    //                        Math                        //
    // ================================================== //

    final public static function abs($number)
    {
        self::ensureType($number, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        return abs($number);
    }

    final public static function sign($number)
    {
        self::ensureType($number, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        if (0 === $number) return 0;
        return (int)(self::abs($number) / $number);
    }

    final public static function min()
    {
        $arg_list = func_get_args();
        if (0 === count($arg_list)) throw new UserException('Empty $arg_list.');
        if (1 === count($arg_list)) {
            $list = $arg_list[0];
            return self::extremum(self::M_MIN, $list);
        } else {
            return self::extremum(self::M_MIN, $arg_list);
        }
    }

    final public static function max()
    {
        $arg_list = func_get_args();
        if (0 === count($arg_list)) throw new UserException('Empty $arg_list.');
        if (1 === count($arg_list)) {
            $list = $arg_list[0];
            return self::extremum(self::M_MAX, $list);
        } else {
            return self::extremum(self::M_MAX, $arg_list);
        }
    }

    final public static function extremum($type, &$value_list)
    {
        self::ensureIn($type, [ self::M_MIN, self::M_MAX ]);
        self::ensureListOfType($value_list, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        if (0 === count($value_list)) throw new UserException('Empty $value_list.');
        if (1 === count($value_list)) return $value_list[0];
        return call_user_func_array($type, $value_list);
    }

    final public static function round($number, $precision = 0)
    {
        self::ensureType($number, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        if (TURE === self::isInt($number)) $number = (float)$number;
        return round($number, $precision);
    }

    final public static function randomInt($min = 0, $max = NULL)
    {
        $randmax = mt_getrandmax();
        if (TRUE === is_null($max)) $max = $randmax;
        self::ensureInt($min, FALSE, FALSE);
        self::ensureInt($max);
        if ($min > $max) throw new UserException("Min($min) is larger than max($max).", [ $min, $max ]);
        return $min + self::round(1.0 * mt_rand() / $randmax * ($max - $min));
            
    }

    final public static function randomFloat($min = 0, $max = 1)
    {
        self::ensureType($min, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        self::ensureType($max, [ self::TYPE_INT, self::TYPE_FLOAT ]);
        if ($min > $max) throw new UserException("Min($min) is larger than max($max).", [ $min, $max ]);
        return $min + 1.0 * mt_rand() / mt_getrandmax() * ($max - $min);
    }

    final public static function randomSelect($list, $num)
    {
        self::ensureArray($list);
        self::ensureInt($num);
        if ($num > self::len($list)) $num = self::len($list);
        $result = array_rand($list, $num);
        if (1 === $num) $result = [ $result ];
        $result = array_values(Kit::extract($list, $result));
        return $result;
    }

    /**
     * Utility function for getting random values with weighting.
     * Pass in an associative array, such as
     * [
     *     ['item' => 'A', 'weight' => 5],
     *     ['item' => 'B', 'weight' => 45],
     *     ['item' => 'C', 'weight' => 50]
     * ]
     * An array like this means that "A" has a 5% chance of being selected, "B" 45%, and "C" 50%.
     * The return value is the array key, A, B, or C in this case.
     * Note that the values assigned do not have to be percentages.
     * The values are simply relative to each other.
     * If one value weight was 2, and the other weight of 1,
     * the value with the weight of 2 has about a 66% chance of being selected.
     * Also note that weights should be integers.
     * @param array $list_of_dict
     * @return array
     * @throws UserException if the sum of weights is 0.
     */
    final public static function randomSelectByWeight(&$list_of_dict)
    {
        self::ensureListOfDict($list_of_dict);
        $weight_list = self::columns($list_of_dict, 'weight', TRUE, NULL, TRUE);
        self::ensureAllSame(self::mapped([ 'self', 'sign' ], $weight_list), 1);
        $sum = self::sum($weight_list);
        if (0 === $sum) throw new UserException('The sum of weights is 0.', $list_of_dict);
        throw new UserException('randomByWeight TODO');
        // $randmax = getrandmax();
        // $rand = mt_rand(1, (int)$randmax);
        // // @todo: use bisection method when length of $list_of_dict > 50!
        // foreach ($list_of_dict as $object) {
        //     $rand -= $object['weight'];
        //     if ($rand <= 0) return $object['item'];
        // }
    }
}