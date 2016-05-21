<?php

namespace Ilex\Core;

/**
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Core
 * 
 * @method final public static json(mixed $data)
 * @method final public static redirect(string $url)
 */
final class Http
{
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