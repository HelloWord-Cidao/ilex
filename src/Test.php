<?php

namespace Ilex;

use \Ilex\Core\Loader;

/**
 * Class Test
 * @package Ilex
 * 
 * @property public static \Ilex\Base\Model\sys\Input $Input
 * 
 * @method public static        boot($APPPATH, $RUNTIMEPATH)
 * @method public static string run($url = '/', $method = 'GET', $post = [], $get = [])
 */
class Test
{
    public static $Input;
    public static $Session;

    /**
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function boot($APPPATH, $RUNTIMEPATH)
    {
        define('ENVIRONMENT', 'TEST');
        Autoloader::initialize($APPPATH, $RUNTIMEPATH);
        self::$Input   = Loader::model('sys/Input');
        self::$Session = Loader::model('sys/Session');
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $post
     * @param array  $get
     * @return string
     */
    public static function run($url = '/', $method = 'GET', $post = [], $get = [])
    {
        self::$Input->clear()->merge('post', $post)->merge('get', $get);
        $_SERVER['REQUEST_URI'] =  ENV_HOST . '/' . $url;
        ob_start();
        // @todo: echo?
        Autoloader::resolve($method, $url);
        return ob_get_clean();
    }
}