<?php

namespace Ilex\Lib;

use \Ilex\Lib\Debug;

/**
 * @todo: method arg type validate
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Lib
 * 
 * @method public static string escape(string $data)
 * @method public static        json(mixed $data)
 * @method public static        redirect(string $url)
 */
final class Http
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
     * @param mixed $data
     */
    public static function json($data)
    {
        echo json_encode($data);
    }

    /**
     * @param string $url
     */
    public static function redirect($url)
    {
        if (TRUE === Debug::isProduction()) {
            header('Location: ' . $url);
        }
    }
}