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
 * @property private boolean $cancelled
 * @property private string  $method
 * @property private         $result
 * @property private boolean $settled
 * @property private string  $uri
 * @property private array   $uriList
 * 
 * @method public          __call(string $method_name, array $arg_list)
 * @method public          __construct(string $method, string $uri)
 * @method public  string  __toString()
 * Methods derived from __call():
 *   @method public        any       (string $description, mixed $handler, string $function = NULL)
 *   @method public        controller(string $description, string $handler)
 *   @method public        get       (string $description, mixed $handler, string $function = NULL)
 *   @method public        group     (string $description, callable $handler)
 *   @method public        post      (string $description, mixed $handler, string $function = NULL)
 * @method public  boolean back()
 * @method public  mixed   result()
 *
 * @method private              end(mixed $result)
 * @method private boolean      fitController(string $description, string $handler)
 * @method private boolean      fitGeneral(string $description, mixed $handler
 *                                  , string|NULL $function = NULL, boolean $is_time_consuming = FALSE)
 * @method private array|string getFunction(string $uri)
 * @method private string       getPattern(string $description)
 * @method private              popUriList()
 * @method private boolean      resolveRestURI(string $description)
 */
final class Router
{
    private $cancelled = FALSE; // @todo: what?
    private $method;            // i.e.  'GET' | 'HEAD' | 'POST' | 'PUT'
    private $result    = NULL;
    private $settled   = FALSE;
    private $uri;
    private $uriList   = [];

    /**
     * @param string $method eg. 'GET' | 'POST' | 'PUT'
     * @param string $uri
     */
    public function __construct($method, $uri)
    {
        // The only place where $method is assigned.
        $this->method = $method;
        // The first of three places where $this->uri is assigned.
        $this->uri    = $uri;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result  = PHP_EOL . '\Router {' . PHP_EOL;
        $result .= "\tcancelled : " . Kit::toString($this->cancelled) . PHP_EOL;
        $result .= "\tmethod    : " . Kit::toString($this->method)    . PHP_EOL;
        $result .= "\tresult    : " . Kit::toString($this->result)    . PHP_EOL;
        $result .= "\tsettled   : " . Kit::toString($this->settled)   . PHP_EOL;
        $result .= "\turi       : " . Kit::toString($this->uri)       . PHP_EOL;
        $result .= "\turiList   : " . Kit::toString($this->uriList)   . PHP_EOL;
        $result .= '}';
        return $result;
    }

    /**
     * Checks the method and then attempts to fit the request..
     * @param string $method_name i.e. 'any' | 'get' | 'post' | 'controller' | 'group'
     * @param array  $arg_list
     */
    public function __call($method_name, $arg_list)
    {
        if (FALSE === $this->settled) {
            Kit::log([__METHOD__, [
                'arg_list' => $arg_list,
                'method'   => $this->method,
                'name'     => $method_name,
                'settled'  => $this->settled,
            ]]);
            
            if ('any' === $method_name OR strtolower($this->method) === $method_name) {
            // $arg_list must consists of two or three argument.
                Kit::log([__METHOD__, 'call fitGeneral'], FALSE);
                call_user_func_array([$this, 'fitGeneral'], $arg_list);
            
            } else if ('controller' === $method_name OR 'group' === $method_name) {
            // $arg_list must consists of exactly two argument.
                $description = $arg_list[0];
                $handler     = $arg_list[1];
                
                /**
                 * eg. $this->uri     : '/about/join/whatever'
                 *     $this->uriList : []
                 *     $description   : '/about'
                 */
                if (FALSE === $this->resolveRestURI($description)) {
                // $description IS NOT a prefix of $this->uri, CAN NOT FIT!
                    Kit::log([__METHOD__, '$description IS NOT a prefix of $this->uri, CAN NOT FIT!']);
                    return;
                }
                Kit::log([__METHOD__, '$description IS a prefix of $this->uri', 'after resolveRestURI',
                    ['this' => $this]]);
                /**
                 * eg. $this->uri     : '/join/whatever'
                 *     $this->uriList : ['/about/join/whatever']
                 *     $description   : '/about'
                 */
                if ('controller' === $method_name) {
                    // eg. $description : '/about'
                    //     $handler     : 'About'
                    Kit::log([__METHOD__, 'call fitController'], FALSE);
                    $this->fitController($description, $handler);
                }
            
                if ('group' === $method_name) {
                // Group routes should implemented in order!!!
                    // eg. $description : '/whatever'
                    //     $handler     : an anonymous function usually with an argument: $Router
                    Kit::log([__METHOD__, 'call end', [
                        'handler'  => $handler,
                        'arg_list' => $this,
                    ]]);
                    $this->end(call_user_func($handler, $this));
                }
            }
        }
    }

    /**
     * If $description IS a prefix of $this->uri, then pushes $this->uri into $this->uriList,
     * and extracts the rest uri from $this->uri, and updates it and returns TRUE,
     * else returns FALSE.
     * If $description is same as $this->uri, then '/' will be returned.
     * eg. $description : '/about'
     *     $this->uri   : '/about/join/whatever' => '/join/whatever'
     *     $this->uri   : '/about' => '/'
     * @param string $description eg. '/about'
     * @return boolean
     */
    private function resolveRestURI($description)
    {
        $length = Kit::len($description);
        if (substr($this->uri, 0, $length) !== $description) {
        // $description IS NOT a prefix of $this->uri.
            return FALSE;
        } else {
        // $description IS a prefix of $this->uri, eg. '/about/join/whatever'.
            // The first of two places where $this->uriList is updated.
            $this->uriList[] = $this->uri;
            // The second of three places where $this->uri is assigned.
            $this->uri = (
                FALSE === ($uri = substr($this->uri, $length)) ? '/' : $uri
            );
            return TRUE;
        }
    }

    /**
     * @todo what?
     * Cancels something?
     * @return boolean
     */
    public function back()
    {
        Kit::log([__METHOD__]);
        if (TRUE === $this->settled) {
            Kit::log([__METHOD__, 'return FALSE'], FALSE);
            return FALSE;
        } else {
            // @todo: need test!
            $this->popUriList();
            $this->cancelled = TRUE;
            Kit::log([__METHOD__, 'self::popUriList()', 'cancelled = TRUE', 'return TRUE'], FALSE);
            return TRUE;
        }
    }

    /**
     * Once called by Autoloader::resolve()
     * @return mixed
     */
    public function result()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    private function end($result)
    {
        Kit::log([__METHOD__, ['result' => $result]]);
        // @todo: what? when to use $this->cancelled?
        if (TRUE === $this->cancelled) {
            // @todo: need test!
            $this->cancelled = FALSE;
        } else {
            $this->settled = TRUE;
            $this->result = $result;
        }
        Kit::log([__METHOD__, ['this' => $this]]);
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
    private function fitGeneral($description, $handler, $function = NULL, $is_time_consuming = FALSE)
    {
        Kit::log([__METHOD__, [
            'description'       => $description,
            'function'          => $function,
            'handler'           => $handler,
            'is_time_consuming' => $is_time_consuming,
        ]]);
        Kit::log([__METHOD__, ['this' => $this]]);
        /**
         * eg. $description : '/project/(id:num)' => '/project/([0-9]+?)'
         *     $this->uri   : '/project/12'
         *     $match_list  : ['/project/12', '12']
         */
        $pattern = $this->getPattern($description); // It will attempt to match the whole $this->uri string.
        $uri     = rtrim($this->uri, '/'); // $this->uri contains no GET args.
        Kit::log([__METHOD__, [
            'pattern' => $pattern,
            'uri'     => $uri,
        ]]);
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
                Kit::log([__METHOD__, '$handler is a string or IS NOT an anonymous function, i.e.
                    , an instance.'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'function' => $function,
                    'handler'  => TRUE === Kit::isString($handler) ? Loader::loadService($handler) : $handler,
                    'arg_list' => [ $is_time_consuming ],
                ]]);
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
                Kit::log([__METHOD__, '$handler is an anonymous function.'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'handler'  => $handler,
                    'arg_list' => [ $is_time_consuming ],
                ]]);
                $this->end(call_user_func_array($handler, [ $is_time_consuming ]));
            }
            Kit::log([__METHOD__, 'CAN FIT!'], FALSE);
            return TRUE;
        } else {
            // CAN NOT FIT!
            Kit::log([__METHOD__, 'CAN NOT FIT!'], FALSE);
            return FALSE;
        }
    }

    /**
     * @todo: use more elegant regex.
     * @param string $description
     * @return string
     */
    private function getPattern($description)
    {
        foreach ([
                // '(all)' => '(.+?)',
                // '(any)' => '([^/]+?)',
                // '(num)' => '([0-9]+?)',
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
     * Extracts function and arg_list and handles the request,
     * by calling the appropriate method
     * (calling 'resolve' method if method $function does NOT exist).
     * @param string $description eg. '/about'
     * @param string $handler     eg. 'About'
     * @return boolean
     */
    private function fitController($description, $handler)
    {
        /**
         * eg. $description   : '/about'
         *     $handler       : 'About'
         *     $this->uri     : '/join/whatever'
         *     $this->uriList : ['/about/join/whatever']
         */
        Kit::log([__METHOD__, [
            'desc'    => $description,
            'handler' => $handler,
        ]]);
        Kit::log([__METHOD__, ['this' => $this]]);

        $function = $this->getFunction($this->uri);
        Kit::log([__METHOD__, ['function' => $function]]);
        // if (TRUE === Kit::isList($function)) {
        if (TRUE === Kit::isArray($function)) { // @CAUTION
            // CAN NOT change order!
            $arg_list = $function[1];
            $function = $function[0];
        } else {
            $arg_list = [];
        }
        /**
         * eg. $function : ['join', ['whatever']], then 'join'
         *     $arg_list   : ['whatever']
         */
        Kit::log([__METHOD__, 'after getFunction', [
            'function' => $function,
            'arg_list' => $arg_list,
        ]]);
        
        // The controller is loaded HERE!
        $controller  = Loader::loadController($handler); // eg. \AboutController
        $combination = strtolower($this->method) . ucfirst($function); // eg. 'postJoin'
        Kit::log([__METHOD__, ['controller' => $controller, 'combination' => $combination]]);
        // @todo: possibly conflict!? Test cases concerned with the default method: 'get'!
        if (TRUE === method_exists($controller, $combination)) { // eg. AboutController::postJoin().
            Kit::log([__METHOD__, 'method_exists($controller, $combination)'], FALSE);
            $fn = $combination;
        } elseif (TRUE === method_exists($controller, $function)) { // eg. AboutController::join()
            Kit::log([__METHOD__, 'method_exists($controller, $function)'], FALSE);
            $fn = $function;
        } elseif (TRUE === method_exists($controller, 'resolve')) { // eg. AboutController::resolve()
            Kit::log([__METHOD__, 'method_exists($controller, \'resolve\')'], FALSE);
            $fn = 'resolve';
            $arg_list = [$this];
        } else {
            // CAN NOT FIT! Rollback!
            // @todo: need test!
            Kit::log([__METHOD__, 'call popUriList', ['this' => $this]]);
            $this->popUriList();
            // eg. $this->uri     : '/about/join/whatever'
            //     $this->uriList : []
            Kit::log([__METHOD__, 'after popUriList', ['this' => $this]]);
            Kit::log([__METHOD__, 'CAN NOT FIT! Rollback!'], FALSE);
            return FALSE;
        }

        Kit::log([__METHOD__, 'call end', [
            'function' => $fn,
            'handler'  => $controller,
            'arg_list' => $arg_list,
        ]]);
        $this->end(call_user_func_array([$controller, $fn], $arg_list));
        Kit::log([__METHOD__, 'CAN FIT!'], FALSE);
        return TRUE;
    }

    /**
     * Returns [$function, $arg_list] if args found, else returns $function.
     * @param string $uri   eg. '/user/page/12'
     * @return array|string eg. ['user', ['page', '12']]
     */
    private function getFunction($uri)
    {
        Kit::log([__METHOD__, ['uri' => $uri]]);
        $index = strpos($uri, '/');
        if (0 === $index) {
        // $uri begins with '/'.
            if (FALSE === ($uri = substr($uri, 1))) {
                // Fails to exclude '/' because $uri is '/'.
                $uri   = '';
                $index = FALSE;
            } else {
                // Now the first '/' is excluded. Search for the second '/' in $uri.
                $index = strpos($uri, '/');
            }
        }
        if (FALSE === $index) {
        // '/' NOT found.
            $function   = $uri; // It will be '' if $uri was '/' at the beginning!
            $arg_list = [];   // eg. '/user' => 'user' with no arg_list
        } else {
        // '/' found.
            $function = substr($uri, 0, $index); // eg. 'user'
            if (FALSE === ($arg_raw = substr($uri, $index + 1))) {
                // @todo: need test!
                $arg_list = []; // eg. '/user/' => 'user/' with no arg_list
            } else {
                // At least one arg.
                $arg_list = Kit::split('/', substr($uri, $index + 1)); // eg. ['page', '12']
            }
        }
        if ('' === $function) {
            $function = 'index'; // $uri was '/' at the beginning.
        }
        Kit::log([__METHOD__, ['function' => $function, 'arg_list' => $arg_list]]);
        return Kit::len($arg_list) > 0 ? [ $function, $arg_list ] : $function;
    }

    private function popUriList()
    {
        // The last of three places where $this->uri is assigned.
        // The last of two places where $this->uriList is updated.
        $this->uri = Kit::popList($this->uriList);
    }
}