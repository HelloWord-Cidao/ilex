<?php

namespace Ilex\Lib;
use \Ilex\Lib\Validator;

/**
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 * 
 * @method public static array          extractException(\Exception $exception)
 * @method public static string         escape(string $data)
 * @method public static string         getRealPath(string $path)
 * @method public static                log(mixed $data, boolean $quotationMarks = TRUE, string $env = 'TEST')
 * @method public array|FALSE           randomByWeight(array $random_list)
 * @method public static array          recoverMongoDBQuery(array $query)
 * @method public static string|boolean strToTitle($string)
 * @method public static string         time(int|boolean $time = FALSE, string $format = 'Y-m-d H:i:s')
 * @method public static string         toString(mixed $data, boolean $quotationMarks = TRUE)
 */
class Kit
{

    /**
     * Extracts useful info from an exception.
     * @param \Exception $exception
     * @param boolean    $need_file_info
     * @param boolean    $need_trace_info
     * @return array
     */
    public static function extractException($exception, $need_file_info = FALSE, $need_trace_info = FALSE)
    {
        $result = ([
            'message'       => $exception->getMessage(),
            'code'          => $exception->getCode(),
        ]) + ($need_file_info === FALSE ? [] : [
            'file'          => $exception->getFile(),
            'line'          => $exception->getLine(),
        ]) + ($need_trace_info === FALSE ? [] : [
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
    public static function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Returns canonicalized absolute pathname with a trailing '/'
     * eg. '/home/user/Project/Test/../Test/app' => '/home/user/Project/Test/app/'
     * @param string $path
     * @return string
     */
    public static function getRealPath($path)
    {
        if (($_temp = realpath($path)) !== FALSE) {
            $path = $_temp . '/';
        } else {
            $path = rtrim($path, '/') . '/';
        }
        return $path;
    }

    /**
     * This mehtod logs debug info.
     * @param mixed  $data
     * @param boolean $quotationMarks indicates whether to include quotation marks when dealing with strings
     * @param string $env
     */
    public static function log($data, $quotationMarks = TRUE, $env = 'TESTILEX')
    {
        if (ENVIRONMENT === $env) {
            $result = '';
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if ($key === 0) $result .= static::toString($value, FALSE) . ' : ';
                    else $result .= static::toString($value, $quotationMarks) . "\t";
                }
                $result .= PHP_EOL.'<br>';
            } else {
                $result .= static::toString($data, FALSE) . PHP_EOL.'<br>';
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
    public static function randomByWeight($random_list)
    {
        //take a list of object['Data'=>word(from ContextCollection), 'Weight'=>int()]; randomly select one object based on object['weight']
        $sum = 0;
        foreach ($random_list as $object) $sum += $object['Weight'];
        if ($sum == 0) return FALSE;
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
    public static function recoverMongoDBQuery($query)
    {
        foreach ($query as $key => $value) {
            unset($query[$key]);
            $query[str_replace('_', '.', $key)] = $value;
        }
        return $query;
    }

    /**
     * @param string $string
     * @return string|boolean
     */
    public static function strToTitle($string)
    {
        if (is_string($string)) {
            if (strlen($string) === 0) return $string;
            return strtoupper(substr($string, 0, 1)) . substr($string, 1);
        } else return FALSE;
    }

    /**
     * Converts time and returns it.
     * @param int|boolean $time
     * @param string      $format
     * @return string
     */
    public static function time($time = FALSE, $format = 'Y-m-d H:i:s')
    {
        if ($time === FALSE) {
            $time = time();
        }
        return date($format, $time);
    }

    /**
     * Generates the string form of data.
     * @param mixed   $data
     * @param boolean $quotationMarks indicates whether to include quotation marks when dealing with strings
     * @return string
     */
    public static function toString($data, $quotationMarks = TRUE)
    {
        if (is_array($data)) {
            array_walk(
                $data,
                function(&$datum, $index, $quotationMarks) {
                    $datum = static::toString($datum, $quotationMarks);
                },
                $quotationMarks
            );
        }
        if (Validator::isList($data)) {
            if (count($data) === 0) return '[]';
            return '[ ' . join(', ', $data) . ' ]';
        }
        else if (Validator::isDict($data)) {
            return '{ '
                . join(', ',
                    array_map(
                        function($key, $value) {
                            return static::toString($key, FALSE) . ' : ' . $value;
                        },
                        array_keys($data),
                        array_values($data)
                    )
                ) . ' }';
            
        }
        else if ($data instanceof \Closure) return '\Closure';
        else if (is_object($data) AND method_exists($data, '__toString') === FALSE)
            return '\Object' . '(' . get_class($data) . ')';
        else if (is_bool($data)) return $data ? 'TRUE' : 'FALSE';
        else if (is_null($data)) return 'NULL';
        else if (is_string($data)) return $quotationMarks ? ('\'' . $data . '\'') : $data;
        else return strval($data);
    }
}