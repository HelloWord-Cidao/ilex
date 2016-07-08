<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class Router
 * The class in charge of routing requests.
 * @package Ilex\Core
 * 
 * @property private string  $method
 * @property private string  $uri
 * @property private         $result
 * 
 * @method final public          __call(string $method_name, array $arg_list)
 * @method final public          __construct(string $method, string $uri)
 * Methods derived from __call():
 *   @method final public        any       (string $description, mixed $handler, string $function = NULL)
 *   @method final public        get       (string $description, mixed $handler, string $function = NULL)
 *   @method final public        post      (string $description, mixed $handler, string $function = NULL)
 * @method final public  mixed   result()
 *
 * @method final private boolean fitGeneral(string $description, mixed $handler
 *                             , string|NULL $function = NULL, boolean $is_time_consuming = FALSE)
 * @method final private string  getPattern(string $description)
 * @method final private         end(mixed $result)
 */
final class Router
{
    private $method = NULL; // i.e.  'GET' | 'POST'
    private $uri    = NULL;
    private $result = NULL;

    /**
     * @param string $method eg. 'GET' | 'POST'
     * @param string $uri
     */
    final public function __construct($method, $uri)
    {
        // The only place where $method is assigned.
        $this->method = $method;
        // The first of three places where $this->uri is assigned.
        $this->uri    = $uri;
    }

    /**
     * Checks the method and then attempts to fit the request..
     * @param string $method_name i.e. 'any' | 'get' | 'post'
     * @param array  $arg_list
     */
    final public function __call($method_name, $arg_list)
    {   
        if ('any' === $method_name OR strtolower($this->method) === $method_name) {
            // $arg_list must consists of two or three argument.
            call_user_func_array([$this, 'fitGeneral'], $arg_list);
        }
    }

    /**
     * Once called by Autoloader::resolve()
     * @return mixed
     */
    final public function result()
    {
        return $this->result;
    }

    /**
     * Extracts args and handles the request,
     * by choosing the appropriate handler,
     * and calling the appropriate method
     * if $description CAN fit $this->uri.
     * @param string      $description eg. '/project/(num)', '/(num)', '/', '/user/(any)', '(all)'
     * @param mixed       $handler     eg. 'Project',        $this,    an anonymous function
     * @param string|NULL $function    eg. 'view'
     * @param boolean     $is_time_consuming
     * @return boolean
     */
    final private function fitGeneral($description, $handler, $function = NULL, $is_time_consuming = FALSE)
    {
        /**
         * eg. $description : '/project/(id:num)' => '/project/([0-9]+?)'
         *     $this->uri   : '/project/12'
         *     $match_list  : ['/project/12', '12']
         */
        $pattern = $this->getPattern($description); // It will attempt to match the whole $this->uri string.
        $uri     = rtrim($this->uri, '/'); // $this->uri contains no GET args.
        if (1 === preg_match($pattern, $uri, $match_list)) {
            preg_match_all('@([^:\(\)]+):([^:\(\)]+)@', $description, $m, PREG_SET_ORDER);
            $mapping = [];
            foreach ($m as $value) {
                $mapping[$value[1]] = $value[2]; // 'id' => 'num'
            }
            $Input = Loader::loadInput();
            foreach ($match_list as $key => $value) { // [0 => 12, 'id' => 12]
                if (TRUE === Kit::isInt($key)) {
                    unset($match_list[$key]);
                } elseif ('num' === $mapping[$key]) {
                    $Input->setInput($key, intval($value));
                } else {
                    $Input->setInput($key, $value);
                }
            }
            if (TRUE === Kit::isString($handler) OR FALSE === ($handler instanceof \Closure)) {
                // $handler is a string or IS NOT an anonymous function, i.e., an instance.
                Kit::ensureString($function);
                $this->end(
                    call_user_func_array([
                        // The service controller is loaded HERE!
                        TRUE === Kit::isString($handler) ? Loader::loadService($handler) : $handler,
                        $function
                    ], [ $is_time_consuming ])
                );
            } elseif (TRUE === is_callable($handler)) {
                // $handler is an anonymous function.
                $this->end(call_user_func_array($handler, [ $is_time_consuming ]));
            }
            // CAN FIT!
            return TRUE;
        } else {
            // CAN NOT FIT!
            return FALSE;
        }
    }

    /**
     * @todo: use more elegant regex.
     * @param string $description
     * @return string
     */
    final private function getPattern($description)
    {
        foreach ([
                '('     => '(?P<',
                ':all)' => '>.+?)',
                ':any)' => '>[^/]+?)',
                ':num)' => '>[0-9]+?)',
            ] as $key => $value) {
            $description = str_replace($key, $value, $description);
        }
        $description = rtrim($description, '/');
        return '@^' . $description . '$@';
    }

    /**
     * @param mixed $result
     */
    final private function end($result)
    {
        $this->result = $result;
    }
}