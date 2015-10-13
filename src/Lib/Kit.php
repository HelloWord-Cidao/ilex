<?php

namespace Ilex\Lib;

/**
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 * 
 * @method public static string  escape(string $data)
 * @method public static string  time(int|boolean $time = FALSE, string $format = 'Y-m-d H:i:s')
 * @method public static         log(mixed $data, boolean $quotationMarks = TRUE, string $env = 'TEST')
 * @method public static string  toString(mixed $data, boolean $quotationMarks = TRUE)
 * @method public static boolean isDict(array $array)
 * @method public static boolean isList(array $array)
 */
class Kit
{
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
     * This mehtod logs debug info.
     * @param mixed  $data
     * @param boolean $quotationMarks indicates whether to include quotation marks when dealing with strings
     * @param string $env
     */
    public static function log($data, $quotationMarks = TRUE, $env = 'TEST')
    {
        // print_r($data); echo PHP_EOL;
        if (ENVIRONMENT === $env) {
            $result = '';
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if ($key === 0) $result .= self::toString($value, FALSE) . ' : ';
                    else $result .= self::toString($value, $quotationMarks) . "\t";
                }
                $result .= PHP_EOL;
            } else {
                $result .= self::toString($data, FALSE) . PHP_EOL;
            }
            echo $result;
        }
    }

    /**
     * Generates the string form of data.
     * @param mixed   $data
     * @param boolean $quotationMarks indicates whether to include quotation marks when dealing with strings
     * @return string
     */
    public static function toString($data, $quotationMarks = TRUE)
    {
        // print_r($data); echo PHP_EOL . gettype($data) . PHP_EOL;
        if (is_array($data)) {
            array_walk(
                $data,
                function(&$datum, $index, $quotationMarks) {
                    $datum = self::toString($datum, $quotationMarks);
                    // echo $index . ' ' . $datum . PHP_EOL;
                },
                $quotationMarks
            );
            // echo 'array_walk : ' . PHP_EOL;
            // print_r($data);
            // echo 'done.' . PHP_EOL;
        }
        if (self::isList($data)) {
            if (count($data) === 0) return '[]';
            return '[ ' . join(', ', $data) . ' ]';
        }
        else if (self::isDict($data)) {
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
            
        else if ($data instanceof \Closure) return '\Closure';
        else if (is_object($data) AND !method_exists($data, '__toString'))
            return '\Object' . '(' . get_class($data) . ')';
        else if (is_bool($data)) return $data ? 'TRUE' : 'FALSE';
        else if (is_null($data)) return 'NULL';
        else if (is_string($data)) return $quotationMarks ? ('\'' . $data . '\'') : $data;
        else return strval($data);
    }

    /**
     * Checks whether an array is a dict.
     * @param array $array
     * @return boolean
     */
    public static function isDict($array)
    {
        if (!is_array($array)) return FALSE;
        if (count($array) === 0) return TRUE;
        return !self::isList($array);
    }

    /**
     * Checks whether an array is a list.
     * @param array $array
     * @return boolean
     */
    public static function isList($array)
    {
        if (!is_array($array)) return FALSE;
        if (count($array) === 0) return TRUE;
        return array_keys($array) === range(0, count($array) - 1);
    }
}