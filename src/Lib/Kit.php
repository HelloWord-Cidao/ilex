<?php

namespace Ilex\Lib;
use \Ilex\Lib\Validator;

/**
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 * 
 * @method final public static boolean    checkIsError(mixed $return_value)
 * @method final public static array      generateError(string $description, mixed $detail)
 * @method final public static array      extractException(\Exception $exception)
 * @method final public static array      extractException(\Exception $exception)
 * @method final public static string     escape(string $data)
 * @method final public static string     getRealPath(string $path)
 * @method final public static mixed|NULL last(array $array, int $offset = 1)
 * @method final public static            log(mixed $data, boolean $quotation_mark_list = TRUE, string $env = 'TEST')
 * @method final public array|FALSE       randomByWeight(array $random_list)
 * @method final public static array      recoverMongoDBQuery(array $query)
 * @method final public static array      separateTitleWords(string $string)
 * @method final public static string     time(int|boolean $time = FALSE, string $format = 'Y-m-d H:i:s')
 * @method final public static string     toString(mixed $data, boolean $quotation_mark_list = TRUE)
 */
final class Kit
{

    /**
     * Checks if $return_value is an error info.
     * @param mixed $return_value
     * @return boolean
     */
    final public static function checkIsError($return_value)
    {
        if (TRUE === is_array($return_value) AND TRUE === $return_value[T_IS_ERROR]) return TRUE;
        else return FALSE;
    }

    /**
     * Generates error info with the given description.
     * @param string $description
     * @param mixed  $detail
     * @return array
     */
    final public static function generateError($description, $detail = NULL)
    {
        return [
            T_IS_ERROR => TRUE,
            'desc'     => $description,
            // 'trace'    => array_slice(debug_backtrace(), 1),
        ] + (FALSE === is_null($detail) ? ['detail' => $detail] : []);
    }

    /**
     * Extracts useful info from an exception.
     * @param \Exception $exception
     * @param boolean    $need_file_info
     * @param boolean    $need_trace_info
     * @return array
     */
    final public static function extractException($exception, $need_file_info = FALSE, $need_trace_info = FALSE)
    {
        $result = ([
            'message'       => $exception->getMessage(),
            'code'          => $exception->getCode(),
        ]) + (FALSE === $need_file_info ? [] : [
            'file'          => $exception->getFile(),
            'line'          => $exception->getLine(),
        ]) + (FALSE === $need_trace_info ? [] : [
            'trace'         => $exception->getTrace(),
            // 'traceAsString' => $exception->getTraceAsString(),
        ]);
        return $result;
    }

    /**
     * Escapes html content.
     * @param string $data
     * @return string
     */
    final public static function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

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
     * This mehtod logs debug info.
     * @param mixed  $data
     * @param boolean $quotation_mark_list indicates whether to include quotation marks when dealing with strings
     * @param string $env
     */
    final public static function log($data, $quotation_mark_list = TRUE, $env = 'TESTILEX')
    {
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
     * Utility function for getting random values with weighting.
     * Pass in an associative array, such as
     * [['Data' => 'A', 'Weight' => 5], ['Data' => 'B', 'Weight' => 45], ['Data' => 'C', 'Weight' => 50]]
     * An array like this means that "A" has a 5% chance of being selected, "B" 45%, and "C" 50%.
     * The return value is the array key, A, B, or C in this case.  Note that the values assigned
     * do not have to be percentages.  The values are simply relative to each other.  If one value
     * weight was 2, and the other weight of 1, the value with the weight of 2 has about a 66%
     * chance of being selected.  Also note that weights should be integers.
     *
     * @param array $random_list
     *@return FALSE|array
     */
    final public static function randomByWeight($random_list)
    {
        //take a list of object['Data' => word (from ContextCollection), 'Weight' => int]; randomly select one object based on object['weight']
        $sum = 0;
        foreach ($random_list as $object) $sum += $object['Weight'];
        if (0 === $sum) return FALSE;
        $rand = mt_rand(1, (int) $sum);
        foreach ($random_list as $object) {
            $rand -= $object['Weight'];
            if ($rand <= 0) return $object['Data'];
        }
    }

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

    /**
     * Generates the string form of data.
     * @param mixed   $data
     * @param boolean $quotation_mark_list indicates whether to include quotation marks when dealing with strings
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
        else if (TRUE === $data instanceof \Closure) return '\Closure';
        else if (TRUE === is_object($data) AND FALSE === method_exists($data, '__toString'))
            return '\Object' . '(' . get_class($data) . ')';
        else if (TRUE === is_bool($data)) return TRUE === $data ? 'TRUE' : 'FALSE';
        else if (TRUE === is_null($data)) return 'NULL';
        else if (TRUE === is_string($data)) return TRUE === $quotation_mark_list ? ('\'' . $data . '\'') : $data;
        else return strval($data);
    }
}