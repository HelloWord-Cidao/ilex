<?php

namespace Ilex\Lib;

use \Exception;
use \Ilex\Lib\UserException;
use \Ilex\Lib\Validator;

/**
 * @todo: method arg type validate
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 * 
 * @method final public static int        addTrace(mixed $trace)
 * @method final public static array      column(array $matrix, array|mixed $column_name_list
 *                                            , boolean $set_default = FALSE, mixed $default = NULL
 *                                            , boolean $return_only_values = FALSE)
 * @method final public static array      extractException(Exception $exception
 *                                            , $need_file_info = FALSE
 *                                            , $need_trace_info = FALSE
 *                                            , $need_previous_exception = TRUE)
 * @method final public static string     getRealPath(string $path)
 * @method final public static array      getTraceStack(boolean $reverse = TRUE)
 * @method final public static string     j(mixed $data)
 * @method final public static mixed|NULL last(array $array, int $offset = 1)
 * @method final public static            log(mixed $data, boolean $quotation_mark_list = TRUE
 *                                            , string $env = 'TEST')
 * @method final public array|FALSE       randomByWeight(array $random_list)
 * @method final public static array      recoverMongoDBQuery(array $query)
 * @method final public static array      separateTitleWords(string $string)
 * @method final public static string     time(int|boolean $time = FALSE
 *                                             , string $format = 'Y-m-d H:i:s')
 * @method final public static string     toString(mixed $data, boolean $quotation_mark_list = TRUE)
 * @method final public static string     type(mixed $variable, string $empty_array = 'list')
 */
final class Kit
{

    private static $traceStack = [];

    /**
     * Gets the type of the given variable.
     * @param mixed $variable
     * @param string $variable
     * @return string
     */
    final public static function type($variable, $empty_array = 'list')
    {
        if (FALSE === in_array($empty_array, ['list', 'dict']))
            throw new UserException('Invalid $empty_array value.');
        if (TRUE === is_array($variable)) {
            if (0 === count($variable)) return $empty_array;
            if (array_keys($variable) === range(0, count($variable) - 1)) {
                return 'list';
            } else {
                return 'dict';
            }
        }
        if (TRUE === is_string($variable))   return 'string'; 
        if (TRUE === is_int($variable))      return 'int'; 
        if (TRUE === is_float($variable))    return 'float'; 
        if (TRUE === is_bool($variable))     return 'boolean'; 
        if (TRUE === is_object($variable))   return 'object'; 
        if (TRUE === is_resource($variable)) return 'resource'; 
        if (TRUE === is_null($variable))     return 'null'; 
        throw new UserException('Unknown type of variable given.');
    } 

    // ================================================== //
    //                       String                       //
    // ================================================== //

    /**
     * Separates title words in a string.
     * @param string $string
     * @return array
     */
    final public static function separateTitleWords($string)
    {
        $match_list = [];
        preg_match_all('/[A-Z][a-z]*/', $string, $match_list, PREG_OFFSET_CAPTURE);
        $match_list = $match_list[0];
        if (count($match_list) > 0) {
            $result = [];
            if ($match_list[0][1] > 0) $result[] = substr($string, 0, $match_list[0][1]);
            foreach ($match_list as $match) $result[] = $match[0];
            return $result;
        } else {
            return [$string];
        }
    }

    // ================================================== //
    //                       Array                        //
    // ================================================== //

    /**
     * Returns the last $offset element in $array.
     * @param array $array
     * @param int   $offset
     * @return mixed|NULL
     */
    final public function last($array, $offset = 1)
    {
        if ($offset > count($array)) return NULL;
        return array_slice($array, - $offset)[0];
    }

    /**
     * Extracts columns in a matrix.
     * @param array       $matrix
     * @param array|mixed $column_name_list a list of column names or a column name
     * @param boolean     $set_default whether it needs to set default value for empty fields
     * @param mixed       $default default value to be set for empty fields
     * @param boolean     $return_only_values whether it should return only values
     *                                        when there is only one column to return
     * @return array
     */
    final public static function columns($matrix, $column_name_list, $set_default = FALSE
        , $default = NULL, $return_only_values = FALSE)
    {
        // @todo: define UserTypeException
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
        //     if len(column_name_list) != 1 :
        //         raise Exception('Can not return only values because length 
        //         of column_name_list is not 1.\ncolumn_name_list:\n%s'\
        //             % j(column_name_list))
        //     result = [_.values()[0] for _ in result]
        // return result
        if (FALSE === is_array($column_name_list)) {
            $column_name_list = [ $column_name_list ];
        }
        $result = [];
        foreach ($matrix as $raw_row) {
            $row = [];
            foreach ($column_name_list as $column_name) {
                if (TRUE === isset($raw_row[$column_name])) {
                    $row[$column_name] = $raw_row[$column_name];
                } elseif (TRUE === $set_default) {
                    $row[$column_name] = $default;
                } else {
                    $msg = "Field($column_name) is empty, thus can not be included.";
                    throw new UserException($msg);
                }
            }
            if (TRUE === $return_only_values) {
                if (1 === count($column_name_list)) {
                    $row = $row[0];
                } else {
                    $msg  = 'Can not return only values because length of column_name_list is not 1.';
                    throw new UserException($msg);
                }
            }
            $result[] = $row;
        }
        return $result;
    }

    // ================================================== //
    //                        Data                        //
    // ================================================== //

    /**
     * Prettily prints data.
     * @param mixed $data
     * @return string
     */
    final public static function j($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    // ================================================== //
    //                     Date & Time                    //
    // ================================================== //

    /**
     * Converts time and returns it.
     * @param int|boolean $time
     * @param string      $format
     * @return string
     */
    final public static function time($time = FALSE, $format = 'Y-m-d H:i:s')
    {
        if (FALSE === $time) {
            $time = time();
        }
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
        if (FALSE !== ($_temp = realpath($path))) {
            $path = $_temp . '/';
        } else {
            $path = rtrim($path, '/') . '/';
        }
        return $path;
    }

    // ================================================== //
    //                       MongoDB                      //
    // ================================================== //

    /**
     * Recovers '.' from '_' in a MongoDB query keys.
     * @param array $query
     * @return array
     */
    final public static function recoverMongoDBQuery($query)
    {
        foreach ($query as $key => $value) {
            unset($query[$key]);
            $query[str_replace('_', '.', $key)] = $value;
        }
        return $query;
    }

    // ================================================== //
    //                      Exception                     //
    // ================================================== //

    /**
     * Extracts useful info from an exception.
     * @param Exception  $exception
     * @param boolean    $need_file_info
     * @param boolean    $need_trace_info
     * @return array
     */
    final public static function extractException($exception, $need_file_info = FALSE
        , $need_trace_info = FALSE, $need_previous_exception = TRUE)
    {
        $result = ([
            'message'  => $exception->getMessage(),
            'code'     => $exception->getCode(),
        ]) + (FALSE === $need_file_info ? [] : [
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
        ]) + (FALSE === $need_trace_info ? [] : [
            'trace'    => $exception->getTrace(),
            // 'traceAsString' => $exception->getTraceAsString(),
        ]) + (FALSE === ($exception instanceof UserException) ? [] : [
            'detail'   => $exception->getDetail(),
        ]) + (TRUE === $need_previous_exception 
                AND TRUE  === is_null($exception->getPrevious()) ? [] : [
            'previous' => self::extractException(
                              $exception->getPrevious(),
                              $need_file_info,
                              $need_trace_info
                          ),
        ]);
        return $result;
    }

    // ================================================== //
    //                        Math                        //
    // ================================================== //

    /**
     * Utility function for getting random values with weighting.
     * Pass in an associative array, such as
     * [
     *     ['Data' => 'A', 'Weight' => 5],
     *     ['Data' => 'B', 'Weight' => 45],
     *     ['Data' => 'C', 'Weight' => 50]
     * ]
     * An array like this means that "A" has a 5% chance of being selected, "B" 45%, and "C" 50%.
     * The return value is the array key, A, B, or C in this case.
     * Note that the values assigned do not have to be percentages.
     * The values are simply relative to each other.
     * If one value weight was 2, and the other weight of 1,
     * the value with the weight of 2 has about a 66% chance of being selected.
     * Also note that weights should be integers.
     * @param array $random_list
     *@return FALSE|array
     */
    final public static function randomByWeight($random_list)
    {
        //take a list of object['Data' => word (from ContextCollection), 
        //'Weight' => int]; randomly select one object based on object['weight']
        $sum = 0;
        foreach ($random_list as $object) $sum += $object['Weight'];
        if (0 === $sum) return FALSE;
        $rand = mt_rand(1, (int) $sum);
        foreach ($random_list as $object) {
            $rand -= $object['Weight'];
            if ($rand <= 0) return $object['Data'];
        }
    }

    // ================================================== //
    //                        Debug                       //
    // ================================================== //
    
    /**
     * Adds script execution trace info to the trace stack.
     * @param mixed $trace
     * @return int Current size of the trace stack.
     */
    final public static function addTrace($trace)
    {
        self::$traceStack[] = $trace;
        return count(self::traceStack());
    }

    /**
     * Gets the trace stack in reverse order.
     * @param boolean $reverse
     * @return array
     */
    final public static function getTraceStack($reverse = TRUE)
    {
        if (TRUE === $reverse) {
            return array_reverse(self::$traceStack);
        }
        else {
            return self::$traceStack;
        }
    }

    /**
     * This mehtod logs debug info.
     * @param mixed  $data
     * @param boolean $quotation_mark_list indicates whether to include quotation marks
     *                                     when dealing with strings
     * @param string $env
     */
    final public static function log($data, $quotation_mark_list = TRUE, $env = 'TESTILEX')
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
            } else {
                $result .= self::toString($data, FALSE) . PHP_EOL.'<br>';
            }
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
    final public static function toString($data, $quotation_mark_list = TRUE)
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
        else if (TRUE === $data instanceof \Closure)
            return '\Closure';
        else if (TRUE === is_object($data) AND FALSE === method_exists($data, '__toString'))
            return '\Object' . '(' . get_class($data) . ')';
        else if (TRUE === is_bool($data))
            return TRUE === $data ? 'TRUE' : 'FALSE';
        else if (TRUE === is_null($data))
            return 'NULL';
        else if (TRUE === is_string($data))
            return TRUE === $quotation_mark_list ? ('\'' . $data . '\'') : $data;
        else return strval($data);
    }
}