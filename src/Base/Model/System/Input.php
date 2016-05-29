<?php

namespace Ilex\Base\Model\System;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * @todo: method arg type validate
 * Class Input
 * Encapsulation of system input, such as $_GET, $_POST.
 * @package Ilex\Base\Model\System
 * 
 * @property private static \Ilex\Lib\Container $getData
 * @property private static \Ilex\Lib\Container $postData
 * @property private static \Ilex\Lib\Container $inputData
 * 
 * @method final public                __construct()
 * @method final public static boolean clear(string $name = NULL)
 * @method final public static mixed   get(string $key = NULL, mixed $default = NULL)
 * @method final public static boolean hasGet(array $key_list)
 * @method final public static boolean hasInput(array $key_list)
 * @method final public static boolean hasPost(array $key_list)
 * @method final public static mixed   input(string $key = NULL, mixed $default = NULL)
 * @method final public static boolean merge(string $name, array $data = [])
 * @method final public static array   missGet(array $key_list)
 * @method final public static array   missInput(array $key_list)
 * @method final public static array   missPost(array $key_list)
 * @method final public static mixed   post(string $key = NULL, mixed $default = NULL)
 * @method final public static mixed   setInput(mixed $key, mixed $value)
 * @method final public static boolean deleteInput(mixed $key)
 */
class Input extends BaseModel
{
    private static $getData;
    private static $postData;
    private static $inputData;

    /**
     * Encapsulates global variables.
     */
    final public function __construct()
    {
        unset($_GET['_url']);
        self::$getData  = new Container($_GET);
        self::$postData = new Container($_POST);
        $data = json_decode(file_get_contents('php://input'), TRUE);
        if (FALSE === is_null($data)) self::merge('post', $data);
        self::$inputData = new Container(self::$postData->get() + self::$getData->get());
    }

    /**
     * If $name is NOT assigned, $getData, $postData and $inputData will both be cleared.
     * @param string $name
     * @return self
     */
    final public static function clear($name = NULL)
    {
        if (FALSE === is_null($name)) {

            if (TRUE === in_array($name, ['get', 'post', 'input'])) {
                $name .= 'Data';
                self::$$name->assign();
                return TRUE;
            } else return FALSE;
        } else {
            self::$getData->assign();
            self::$postData->assign();
            self::$inputData->assign();
            return TRUE;
        }
    }

    /**
     * @param array $key_list
     * @return boolean
     */
    final public static function hasInput($key_list)
    {
        return call_user_func_array([self::$inputData, 'has'], $key_list);
    }
    
    /**
     * @param array $key_list
     * @return boolean
     */
    final public static function hasGet($key_list)
    {
        return call_user_func_array([self::$getData, 'has'], $key_list);
    }

    /**
     * @param array $key_list
     * @return boolean
     */
    final public static function hasPost($key_list)
    {
        return call_user_func_array([self::$postData, 'has'], $key_list);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    final public static function input($key = NULL, $default = NULL)
    {
        return self::$inputData->get($key, $default);

    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    final public static function get($key = NULL, $default = NULL)
    {
        return self::$getData->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    final public static function post($key = NULL, $default = NULL)
    {
        return self::$postData->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    final public static function setInput($key, $value)
    {
        return self::$inputData->set($key, $value);
    }

    /**
     * @param string $key
     * @return boolean
     */
    final public static function deleteInput($key)
    {
        return self::$inputData->delete($key);
    }

    /**
     * @param string $name
     * @param array  $data
     * @return boolean
     */
    final public static function merge($name, $data = [])
    {
        if (TRUE === in_array($name, ['get', 'post', 'input'])) {
            $name .= 'Data';
            self::$$name->merge($data);
            self::$inputData->merge(self::$postData->get() + self::$getData->get());
            return TRUE;
        } else return FALSE;
    }

    /**
     * @param array $key_list
     * @return array
     */
    final public static function missInput($key_list)
    {
        return self::$inputData->miss($key_list);
    }

    /**
     * @param array $key_list
     * @return array
     */
    final public static function missGet($key_list)
    {
        return self::$getData->miss($key_list);
    }

    /**
     * @param array $key_list
     * @return array
     */
    final public static function missPost($key_list)
    {
        return self::$postData->miss($key_list);
    }
}
