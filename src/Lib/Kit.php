<?php

namespace Ilex\Lib;

use \Closure;
use \Ilex\Lib\UserException;
use \Ilex\Lib\Validator;

/**
 * @todo: method arg type validate
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 *
 * @method public static array      columns(array $matrix, array|mixed $column_name_list
 *                                      , boolean $set_default = FALSE, mixed $default = NULL
 *                                      , boolean $return_only_values = FALSE)
 * @mehtod public static array      columnsExclude(array $matrix, array $column_name_list
 *                                      , boolean $check_existence = FALSE)
 * @method public static array      exclude(array $array, array $field_name_list
 *                                      , boolean $check_existence = FALSE)
 * @method public static array      extract(array $array, array $field_name_list
 *                                      , boolean $set_default = FALSE, mixed $default = NULL)
 * @method public static string     getRealPath(string $path)
 * @method public static string     j(mixed $data)
 * @method public static mixed|NULL last(array $array, int $offset = 1)
 * @method public static            log(mixed $data, boolean $quotation_mark_list = TRUE
 *                                      , string $env = 'TEST')
 * @method public array|FALSE       randomByWeight(array $random_list)
 * @method public static array      recoverMongoDBQuery(array $query)
 * @method public static array      separateTitleWords(string $string)
 * @method public static string     time(int|NULL $time = NULL, string $format = 'Y-m-d H:i:s')
 * @method public static string     toString(mixed $data, boolean $quotation_mark_list = TRUE)
 * @method public static string     type(mixed $variable, string $empty_array = 'list')
 */
final class Kit
{

    const TYPE_STRING   = 'TYPE_STRING';
    const TYPE_INT      = 'TYPE_INT';
    const TYPE_FLOAT    = 'TYPE_FLOAT';
    const TYPE_BOOLEAN  = 'TYPE_BOOLEAN';
    const TYPE_LIST     = 'TYPE_LIST';
    const TYPE_DICT     = 'TYPE_DICT';
    const TYPE_OBJECT   = 'TYPE_OBJECT';
    const TYPE_RESOURCE = 'TYPE_RESOURCE';
    const TYPE_NULL     = 'TYPE_NULL';

    public static function isList($variable)
    {
        return self::TYPE_LIST === self::type($variable, self::TYPE_LIST);
    }

    public static function isDict($variable)
    {
        return self::TYPE_DICT === self::type($variable, self::TYPE_DICT);
    }

    /**
     * Gets the type of the given variable.
     * @param mixed $variable
     * @param string $variable
     * @return string
     * @throws UserException if $empty_array is invalid or type of $variable unknown.
     */
    public static function type($variable, $empty_array = self::TYPE_LIST)
    {
        if (FALSE === in_array($empty_array, [self::TYPE_LIST, self::TYPE_DICT]))
            throw new UserException("Invalid \$empty_array($empty_array).");
        if (TRUE === is_array($variable)) {
            if (0 === count($variable)) return $empty_array;
            if (array_keys($variable) === range(0, count($variable) - 1))
                return self::TYPE_LIST;
            else return self::TYPE_DICT;
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

    // ================================================== //
    //                       String                       //
    // ================================================== //

    /**
     * Separates title words in a string.
     * @param string $string
     * @return array
     */
    public static function separateTitleWords($string)
    {
        $match_list = [];
        preg_match_all('/[A-Z][a-z]*/', $string, $match_list, PREG_OFFSET_CAPTURE);
        $match_list = $match_list[0];
        if (count($match_list) > 0) {
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

    /**
     * Returns the last $offset element in $array.
     * @param array $array
     * @param int   $offset
     * @return mixed|NULL
     * @throws UserException if $offset overflows $array.
     */
    public function last($array, $offset = 1)
    {
        if ($offset > count($array))
            throw new UserException("\$offset($offset) overflows $array.", $array);
        return array_slice($array, - $offset)[0];
    }

    /**
     * Extracts columns in a matrix.
     * @param array       $matrix
     * @param array|mixed $column_name_list   a list of column names or a column name
     * @param boolean     $set_default        whether it needs to set default value for empty fields
     * @param mixed       $default            default value to be set for empty fields
     * @param boolean     $return_only_values whether it should return only values
     *                                        when there is only one column to return
     * @return array
     * @throws UserException if field($column_name) is empty, or attempting return only values
     *                       when the length of $column_name_list is not 1.
     */
    public static function columns($matrix, $column_name_list, $set_default = FALSE
        , $default = NULL, $return_only_values = FALSE)
    {
        if (FALSE === is_array($column_name_list))
            $column_name_list = [ $column_name_list ];
        $result = [];
        foreach ($matrix as $raw_row) {
            $row = self::extract($raw_row, $column_name_list, $set_default, $default);
            if (TRUE === $return_only_values) {
                if (1 === count($column_name_list)) {
                    $row = $row[0];
                } else {
                    $msg  = 'Can not return only values, '
                        . 'because the length of $column_name_list is not 1.';
                    throw new UserException($msg, $column_name_list);
                }
            }
            $result[] = $row;
        }
        return $result;
        // $expected_types = [list, str, unicode, int, float, bool]
        // if type(column_name_list) not in expected_types :
            // raise UserTypeError('column_name_list', column_name_list, expected_types)
        // if type(column_name_list) is not list :
            // column_name_list = [column_name_list]
        // if set_default is True :
        //     result = find(matrix, projection = map_to(column_name_list, 1)
        //     , raise_empty_exception = False, set_default = set_default, default = default)
        // else :
        //     result = find(matrix, projection = map_to(column_name_list, 1)
        //     , raise_empty_exception = True)
        // if return_only_values is True :
        //     if len(column_name_list) !== 1 :
        //         raise Exception('Can not return only values because length 
        //         of column_name_list is not 1.\ncolumn_name_list:\n%s'\
        //             % j(column_name_list))
        //     result = [_.values()[0] for _ in result]
        // return result
    }

    /**
     * Extracts columns in a matrix by excluding specific fields.
     * @param array       $matrix
     * @param array|mixed $column_name_list a list of column names or a column name
     * @param boolean     $check_existence  whether it will check the existence of the to-be-excluded field
     * @return array
     */
    public static function columnsExclude($matrix, $column_name_list, $check_existence = FALSE)
    {
        if (FALSE === is_array($column_name_list))
            $column_name_list = [ $column_name_list ];
        $result = [];
        foreach ($matrix as $raw_row) {
            $result[] = self::exclude($raw_row, $column_name_list, $check_existence);
        }
        return $result;
    }

    /**
     * Extracts fields in an array.
     * @param array       $array
     * @param array|mixed $field_name_list a list of field names
     * @param boolean     $set_default     whether it needs to set default value for empty fields
     * @param mixed       $default         default value to be set for empty fields
     * @return array
     * @throws UserException if field($field_name) is empty
     */
    public static function extract($array, $field_name_list, $set_default = FALSE, $default = NULL)
    {
        if (FALSE === is_array($field_name_list))
            $field_name_list = [ $field_name_list ];
        $result = [];
        foreach ($field_name_list as $field_name) {
            if (TRUE === isset($array[$field_name])) {
                $result[$field_name] = $array[$field_name];
            } elseif (TRUE === $set_default) {
                $result[$field_name] = $default;
            } else {
                $msg = "Field($field_name) is empty, thus can not be included.";
                throw new UserException($msg, $array);
            }
        }
        return $result;
    }

    /**
     * Extracts fields in an array by excluding specific fields.
     * @param array       $array
     * @param array|mixed $field_name_list a list of field names
     * @param boolean     $check_existence whether it will check the existence of the to-be-excluded field
     * @return array
     */
    public static function exclude($array, $field_name_list, $check_existence = FALSE)
    {
        if (FALSE === is_array($field_name_list))
            $field_name_list = [ $field_name_list ];
        $result = $array;
        foreach ($field_name_list as $field_name) {
            if (FALSE === isset($array[$field_name]) AND TRUE === $check_existence) {
                $msg = "Field($field_name) does not exist, thus can not be exclued.";
                throw new UserException($msg, $array);
            }
            unset($result[$field_name]);
        }
        return $result;
    }

    /**
     * @param array $key_list
     * @param array $value_list
     * @return array
     */
    public static function dict($key_list, $value_list)
    {
        return array_combine($key_list, $value_list);
    }

    // ================================================== //
    //                        Data                        //
    // ================================================== //

    /**
     * Prettily prints data.
     * @param mixed $data
     * @return string
     */
    public static function j($data)
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
    public static function time($time = NULL, $format = 'Y-m-d H:i:s')
    {
        if (FALSE === $time) $time = time();
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
    public static function getRealPath($path)
    {
        if (FALSE !== ($realpath = realpath($path)))
            $path = $realpath . '/';
        else $path = rtrim($path, '/') . '/';
        return $path;
    }

    // ================================================== //
    //                        Math                        //
    // ================================================== //

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
     * @param array $random_list
     * @return array
     * @throws UserException if the sum of weights is 0.
     */
    public static function randomByWeight($random_list)
    {
        $sum = 0;
        foreach ($random_list as $object) $sum += $object['weight'];
        if (0 === $sum) throw new UserException('The sum of weights is 0.', $random_list);
        $rand = mt_rand(1, (int)$sum);
        // @todo: use bisection method when length of $random_list > 50!
        foreach ($random_list as $object) {
            $rand -= $object['weight'];
            if ($rand <= 0) return $object['item'];
        }
    }

    /**
     * @todo: remove $env
     * This mehtod logs debug info.
     * @param mixed  $data
     * @param boolean $quotation_mark_list indicates whether to include quotation marks
     *                                     when dealing with strings
     * @param string $env
     */
    public static function log($data, $quotation_mark_list = TRUE, $env = 'TESTILEX')
    {
        // @todo: use json_encode
        if ($env === ENVIRONMENT) {
            $result = '';
            if (TRUE === is_array($data)) {
                foreach ($data as $key => $value) {
                    if (0 === $key) $result .= self::toString($value, FALSE) . ' : ';
                    else $result .= self::toString($value, $quotation_mark_list) . "\t";
                }
                $result .= PHP_EOL.'<br>';
            } else $result .= self::toString($data, FALSE) . PHP_EOL.'<br>';
            echo $result;
        }
    }

    /**
     * Generates the string form of data.
     * @param mixed   $data
     * @param boolean $quotation_mark_list indicates whether to include quotation marks
     *                                     when dealing with strings
     * @return string
     */
    public static function toString($data, $quotation_mark_list = TRUE)
    {
        if (TRUE === is_array($data)) {
            array_walk(
                $data,
                function(&$datum, $index, $quotation_mark_list) {
                    $datum = self::toString($datum, $quotation_mark_list);
                },
                $quotation_mark_list
            );
        }
        if (TRUE === Validator::isList($data)) {
            if (0 === count($data)) return '[]';
            return '[ ' . join(', ', $data) . ' ]';
        }
        else if (TRUE === Validator::isDict($data)) {
            return '{ '
                . join(', ',
                    array_map(
                        function($key, $value) {
                            return self::toString($key, FALSE) . ' : ' . $value;
                        },
                        array_keys($data),
                        array_values($data)
                    )
                ) . ' }';
            
        }
        else if (TRUE === $data instanceof Closure)
            return 'Closure';
        else if (TRUE === is_object($data) AND FALSE === method_exists($data, '__toString'))
            return 'Object' . '(' . get_class($data) . ')';
        else if (TRUE === is_bool($data))
            return TRUE === $data ? 'TRUE' : 'FALSE';
        else if (TRUE === is_null($data))
            return 'NULL';
        else if (TRUE === is_string($data))
            return TRUE === $quotation_mark_list ? ('\'' . $data . '\'') : $data;
        else return strval($data);
    }
}