<?php

namespace Ilex\Core;

/**
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Core
 * 
 * @method public static redirect(string $url)
 * @method public static json(mixed $data)
 */
class Http
{

    /**
     * @uses ENVIRONMENT
     * @param string $url
     */
    public static function redirect($url)
    {
        if (ENVIRONMENT !== 'TEST') {
            header('Location: ' . $url);
        }
    }

    /**
     * @param mixed $data
     */
    public static function json($data)
    {
        echo(json_encode($data));
    }
}