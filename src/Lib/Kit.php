<?php

namespace Ilex\Lib;

/**
 * Class Kit
 * A kit class.
 * @package Ilex\Lib
 * 
 * @method public static string       escape(string $data)
 * @method public static string       time(int|boolean $time = FALSE, string $format = 'Y-m-d H:i:s')
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

}