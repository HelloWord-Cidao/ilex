<?php

namespace Ilex\Base\Model\System;

use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\BaseModel;

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
 * @method public                __construct()
 * @method public static boolean clear(string $name = NULL)
 * @method public static mixed   get(string $key = NULL, mixed $default = NULL)
 * @method public static boolean hasGet(array $key_list)
 * @method public static boolean hasInput(array $key_list)
 * @method public static boolean hasPost(array $key_list)
 * @method public static mixed   input(string $key = NULL, mixed $default = NULL)
 * @method public static boolean merge(string $name, array $data)
 * @method public static array   missGet(array $key_list)
 * @method public static array   missInput(array $key_list)
 * @method public static array   missPost(array $key_list)
 * @method public static mixed   post(string $key = NULL, mixed $default = NULL)
 * @method public static mixed   setInput(mixed $key, mixed $value)
 * @method public static boolean deleteInput(mixed $key)
 */
final class Input extends BaseModel
{
    private static $getData;
    private static $postData;
    private static $inputData;

    /**
     * Encapsulates global variables.
     */
    public function __construct()
    {
        unset($_GET['_url']);
        self::$getData   = new Container();
        self::$postData  = new Container();
        self::$inputData = new Container();
        self::merge('get', $_GET);
        self::merge('post', $_POST);
        $data = json_decode(file_get_contents('php://input'), TRUE);
        if (FALSE === is_null($data)) self::merge('post', $data);
    }

    /**
     * If $name is NOT assigned, $getData, $postData and $inputData will both be cleared.
     * @param string $name
     * @return boolean
     */
    public static function clear($name = NULL)
    {
        if (FALSE === is_null($name)) {
            if (TRUE === in_array($name, ['get', 'post', 'input'])) {
                $name .= 'Data';
                self::$$name->clear();
                return TRUE;
            } else throw new UserException("Invalid \$name($name).");
        } else {
            self::$getData->clear();
            self::$postData->clear();
            self::$inputData->clear();
            return TRUE;
        }
    }

    /**
     * @param array $key_list
     * @return boolean
     */
    public static function hasInput($key_list)
    {
        return call_user_func_array([self::$inputData, 'has'], $key_list);
    }
    
    /**
     * @param array $key_list
     * @return boolean
     */
    public static function hasGet($key_list)
    {
        return call_user_func_array([self::$getData, 'has'], $key_list);
    }

    /**
     * @param array $key_list
     * @return boolean
     */
    public static function hasPost($key_list)
    {
        return call_user_func_array([self::$postData, 'has'], $key_list);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function input($key = NULL, $default = NULL)
    {
        return self::$inputData->get($key, $default);

    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get($key = NULL, $default = NULL)
    {
        return self::$getData->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function post($key = NULL, $default = NULL)
    {
        return self::$postData->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public static function setInput($key, $value)
    {
        return self::$inputData->set($key, $value);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public static function deleteInput($key)
    {
        return self::$inputData->delete($key);
    }

    /**
     * @param string $name
     * @param array  $data
     * @return boolean
     */
    public static function merge($name, $data)
    {
        if (TRUE === in_array($name, ['get', 'post', 'input'])) {
            $name .= 'Data';
            self::$$name->merge($data);
            /* 
            CAUTION: 
                The + operator returns the right-hand array appended to the left-hand array;
                for keys that exist in both arrays, the elements from the left-hand array will be used,
                and the matching elements from the right-hand array will be ignored.
            
                array_merge — Merge one or more arrays
                array array_merge ( array $array1 [, array $... ] )
                Merges the elements of one or more arrays together so that the values of one
                are appended to the end of the previous one. It returns the resulting array.
                If the input arrays have the same string keys, then the later value for that key will 
                overwrite the previous one. If, however, the arrays contain numeric keys,
                the later value will not overwrite the original value, but will be appended.
                Values in the input array with numeric keys will be renumbered with
                incrementing keys starting from zero in the result array.
            */
            if ($name !== 'input') {
                self::$inputData->merge(self::get());
                self::$inputData->merge(self::post());
            }
            return TRUE;
        } else throw new UserException("Invalid \$name($name).");
    }

    /**
     * @param array $key_list
     * @return array
     */
    public static function missInput($key_list)
    {
        return self::$inputData->miss($key_list);
    }

    /**
     * @param array $key_list
     * @return array
     */
    public static function missGet($key_list)
    {
        return self::$getData->miss($key_list);
    }

    /**
     * @param array $key_list
     * @return array
     */
    public static function missPost($key_list)
    {
        return self::$postData->miss($key_list);
    }
}
