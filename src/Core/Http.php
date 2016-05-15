<?php

namespace Ilex\Core;

/**
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Core
 * 
 * @method public static json(mixed $data)
 * @method public static redirect(string $url)
 */
class Http
{
    /**
     * @param mixed $data
     */
    public static function json($data)
    {
        echo(json_encode($data));
    }

    /**
     * @uses ENVIRONMENT
     * @param string $url
     */
    public static function redirect($url)
    {
        if ('TEST' !== ENVIRONMENT) {
            header('Location: ' . $url);
        }
    }
}