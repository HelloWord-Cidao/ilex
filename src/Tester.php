<?php

namespace Ilex;

use \Ilex\Autoloader;
use \Ilex\Core\Loader;

/**
 * Class Tester
 * @package Ilex
 * 
 * @property public static \Ilex\Base\Model\sys\Input $Input
 * 
 * @method public static        boot($APPPATH, $RUNTIMEPATH)
 * @method public static string run($url = '/', $method = 'GET', $post = [], $get = [])
 */
class Tester
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
        return Autoloader::resolve($method, $url);
    }
}