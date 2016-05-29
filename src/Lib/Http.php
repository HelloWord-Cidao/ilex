<?php

namespace Ilex\Lib;

/**
 * @todo: method arg type validate
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Lib
 * 
 * @method final public static string escape(string $data)
 * @method final public static        json(mixed $data)
 * @method final public static        redirect(string $url)
 */
final class Http
{

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
     * @param mixed $data
     */
    final public static function json($data)
    {
        echo(json_encode($data));
    }

    /**
     * @uses ENVIRONMENT
     * @param string $url
     */
    final public static function redirect($url)
    {
        if ('TEST' !== ENVIRONMENT) {
            header('Location: ' . $url);
        }
    }
}