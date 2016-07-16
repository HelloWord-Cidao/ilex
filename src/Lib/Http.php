<?php

namespace Ilex\Lib;

use \Ilex\Lib\Kit;

/**
 * Class Http
 * The class in charge of http operations.
 * @package Ilex\Lib
 * 
 * @method final public static string escape(string $data)
 * @method final public static        json(mixed $data)
 * @method final public static        request($url, $param, $data = '', $method = 'GET')
 */
final class Http
{

    /**
     * Escapes html content.
     * @param string $string
     * @return string
     */
    final public static function escape($string)
    {
        Kit::ensureString($string);
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array $data
     */
    final public static function json($data)
    {
        Kit::ensureArray($data); // @CAUTION
        echo json_encode($data);
    }

    final public static function request($url, $param, $data = [], $method = 'GET')
    {
        Kit::ensureString($url);
        Kit::ensureArray($param); // @CAUTION
        Kit::ensureArray($data); // @CAUTION
        Kit::ensureIn($method, [ 'GET', 'POST' ]);
        $opts = [
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt_array($curl, $opts);

        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

}