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
 * @method final public static        request($url, $param, $data = '', $method = 'GET')
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
        echo json_encode($data);
    }

    final public static function request($url, $param, $data = '', $method = 'GET')
    {
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