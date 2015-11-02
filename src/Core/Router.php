<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class Router
 * The class in charge of routing requests.
 * @package Ilex\Core
 * 
 * @property private boolean $cancelled
 * @property private string  $method
 * @property private array   $params
 * @property private         $result
 * @property private boolean $settled
 * @property private string  $uri
 * @property private array   $uris
 * 
 * @method public          __call(string $name, array $arguments)
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
 * @method private boolean      fitGeneral(string $description, mixed $handler, string $function = NULL)
 * @method private array|string getFunction(string $uri)
 * @method private string       getPattern(string $description)
 * @method private              merge(array $vars)
 * @method private              pop()
 * @method private boolean      resolveRestURI(string $description)
 */
class Router
{
    private $cancelled = FALSE; // @TODO: what?
    private $method;            // i.e.  'GET' | 'HEAD' | 'POST' | 'PUT'
    private $params    = [];
    private $result    = NULL;
    private $settled   = FALSE;
    private $uri;
    private $uris      = [];

    /**
     * Checks the method and then attempts to fit the request..
     * @param string $name      i.e. 'any' | 'get' | 'post' | 'controller' | 'group'
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        if ($this->settled === FALSE) {
            Kit::log([__METHOD__, [
                'args'    => $arguments,
                'method'  => $this->method,
                'name'    => $name,
                'settled' => $this->settled,
            ]]);
            
            if ($name === 'any' OR $name === strtolower($this->method)) {
            // $arguments must consists of two or three argument.
                Kit::log([__METHOD__, 'call fitGeneral'], FALSE);
                call_user_func_array([$this, 'fitGeneral'], $arguments);
            
            } else if ($name === 'controller' OR $name === 'group') {
            // $arguments must consists of exactly two argument.
                $description = $arguments[0];
                $handler     = $arguments[1];
                
                /**
                 * eg. $this->uri   : '/about/join/whatever'
                 *     $this->uris  : []
                 *     $description : '/about'
                 */
                if ($this->resolveRestURI($description) === FALSE) {
                // $description IS NOT a prefix of $this->uri, CAN NOT FIT!
                    Kit::log([__METHOD__, '$description IS NOT a prefix of $this->uri, CAN NOT FIT!']);
                    return;
                }
                Kit::log([__METHOD__, '$description IS a prefix of $this->uri', 'after resolveRestURI', ['this' => $this]]);
                /**
                 * eg. $this->uri   : '/join/whatever'
                 *     $this->uris  : ['/about/join/whatever']
                 *     $description : '/about'
                 */
                if ($name === 'controller') {
                    // eg. $description : '/about'
                    //     $handler     : 'About'
                    Kit::log([__METHOD__, 'call fitController'], FALSE);
                    $this->fitController($description, $handler);
                }
            
                if ($name === 'group') {
                // Group routes should implemented in order!!!
                    // eg. $description : '/whatever'
                    //     $handler     : an anonymous function usually with an argument: $Router
                    Kit::log([__METHOD__, 'call end', [
                        'handler' => $handler,
                        'params'  => $this,
                    ]]);
                    $this->end(call_user_func($handler, $this));
                }
            }
        }
    }

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
        $result .= "\tparams    : " . Kit::toString($this->params)    . PHP_EOL;
        $result .= "\tresult    : " . Kit::toString($this->result)    . PHP_EOL;
        $result .= "\tsettled   : " . Kit::toString($this->settled)   . PHP_EOL;
        $result .= "\turi       : " . Kit::toString($this->uri)       . PHP_EOL;
        $result .= "\turis      : " . Kit::toString($this->uris)      . PHP_EOL;
        $result .= '}';
        return $result;
    }

    /**
     * @TODO what?
     * Cancels something?
     * @return boolean
     */
    public function back()
    {
        Kit::log([__METHOD__]);
        if ($this->settled) {
            Kit::log([__METHOD__, 'return FALSE'], FALSE);
            return FALSE;
        } else {
            // @TODO: need test!
            $this->pop();
            $this->cancelled = TRUE;
            Kit::log([__METHOD__, 'pop()', 'cancelled = TRUE', 'return TRUE'], FALSE);
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
        // @TODO: what? when to use $this->cancelled?
        if ($this->cancelled) {
            // @TODO: need test!
            $this->cancelled = FALSE;
        } else {
            $this->settled = TRUE;
            $this->result = $result;
        }
        Kit::log([__METHOD__, ['this' => $this]]);
    }

    /**
     * Extracts function and params and handles the request,
     * by calling the appropriate method
     * (calling 'resolve' method if method $function does NOT exist).
     * @param string $description eg. '/about'
     * @param string $handler     eg. 'About'
     * @return boolean
     */
    private function fitController($description, $handler)
    {
        /**
         * eg. $description : '/about'
         *     $handler     : 'About'
         *     $this->uri   : '/join/whatever'
         *     $this->uris  : ['/about/join/whatever']
         */
        Kit::log([__METHOD__, [
            'desc'    => $description,
            'handler' => $handler,
        ]]);
        Kit::log([__METHOD__, ['this' => $this]]);

        $function = self::getFunction($this->uri);
        if (is_array($function)) {
            $function = $function[0];
            $params   = $function[1];
        } else {
            $params = [];
        }
        /**
         * eg. $function : ['join', ['whatever']], then 'join'
         *     $params   : ['whatever']
         */
        Kit::log([__METHOD__, 'after getFunction', [
            'function' => $function,
            'params'   => $params,
        ]]);
        
        // The controller is loaded HERE!
        $controller  = Loader::controller($handler); // eg. \AboutController
        $combination = strtolower($this->method) . Kit::strToTitle($function); // eg. 'postJoin'
        Kit::log([__METHOD__, ['controller' => $controller, 'combination' => $combination]]);
        // @TODO: possibly conflict!? Test cases concerned with the default method: 'get'!
        if (method_exists($controller, $combination)) { // eg. AboutController::postJoin().
            Kit::log([__METHOD__, 'method_exists($controller, $combination)'], FALSE);
            $fn = $combination;
        } elseif (method_exists($controller, $function)) { // eg. AboutController::join()
            Kit::log([__METHOD__, 'method_exists($controller, $function)'], FALSE);
            $fn = $function;
        } elseif (method_exists($controller, 'resolve')) { // eg. AboutController::resolve()
            Kit::log([__METHOD__, 'method_exists($controller, \'resolve\')'], FALSE);
            $fn = 'resolve';
            $params = [$this];
        } else {
            // CAN NOT FIT! Rollback!
            // @TODO: need test!
            Kit::log([__METHOD__, 'call pop', ['this' => $this]]);
            $this->pop();
            // eg. $this->uri  : '/about/join/whatever'
            //     $this->uris : []
            Kit::log([__METHOD__, 'after pop', ['this' => $this]]);
            Kit::log([__METHOD__, 'CAN NOT FIT! Rollback!'], FALSE);
            return FALSE;
        }

        Kit::log([__METHOD__, 'call end', [
            'function' => $fn,
            'handler'  => $controller,
            'params'   => $params,
        ]]);
        $this->end(call_user_func_array([$controller, $fn], $params));
        Kit::log([__METHOD__, 'CAN FIT!'], FALSE);
        return TRUE;
    }

    /**
     * Extracts params and handles the request,
     * by choosing the appropriate handler,
     * and calling the appropriate method
     * (calling `index` method if $function IS NOT defined),
     * if $description CAN fit $this->uri.
     * @param string $description eg. '/project/(num)', '/(num)', '/', '/user/(any)', '(all)'
     * @param mixed  $handler     eg. 'Project',        $this,    an anonymous function
     * @param string $function    eg. 'view'
     * @return boolean
     */
    private function fitGeneral($description, $handler, $function = NULL)
    {
        Kit::log([__METHOD__, [
            'desc'     => $description,
            'function' => $function,
            'handler'  => $handler,
        ]]);
        Kit::log([__METHOD__, ['this' => $this]]);
        /**
         * eg. $description  : '/project/(num)' => '/project/([0-9]+?)'
         *     $this->uri    : 'http://www.test.com/project/12' or '/project/12'?
         *     $this->params : []
         *     $matches      : ['/project/12', '12']
         */
        if (preg_match(self::getPattern($description), $this->uri, $matches)) {
            unset($matches[0]);
            $this->merge($matches); // $this->params updated.
            Kit::log([__METHOD__, 'after merge', ['params' => $this->params]]);
            // eg. $this->params : ['12']
            
            if (is_string($handler) OR ($handler instanceof \Closure) === FALSE) {
            // $handler is a string or IS NOT an anonymous function, i.e., an instance.
                Kit::log([__METHOD__, '$handler is a string or IS NOT an anonymous function, i.e., an instance.'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'function' => is_null($function) ? 'index' : $function,
                    'handler'  => is_string($handler) ? Loader::controller($handler) : $handler,
                    'params'   => $this->params,
                ]]);
                $this->end(
                    call_user_func_array([
                        is_string($handler) ? Loader::controller($handler) : $handler, // The controller is loaded HERE!
                        is_null($function) ? 'index' : $function // The default function is method 'index' of the handler.
                    ], $this->params)
                );
            } elseif (is_callable($handler)) {
            // $handler is an anonymous function.
                Kit::log([__METHOD__, '$handler is an anonymous function.'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'handler' => $handler,
                    'params'  => $this->params,
                ]]);
                $this->end(call_user_func_array($handler, $this->params));
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
     * Returns [$function, $params] if parameters found, else returns $function.
     * @param string $uri   eg. '/user/page/12'
     * @return array|string eg. ['user', ['page', '12']]
     */
    private function getFunction($uri)
    {
        Kit::log([__METHOD__, ['uri' => $uri]]);
        $index = strpos($uri, '/');
        if ($index === 0) {
        // $uri begins with '/'.
            if (($uri = substr($uri, 1)) === FALSE) {
                // Fails to exclude '/' because $uri is '/'.
                $uri = '';
                $index = FALSE;
            } else {
                // Now the first '/' is excluded. Search for the second '/' in $uri.
                $index = strpos($uri, '/');
            }
        }
        if ($index === FALSE) {
        // '/' NOT found.
            $function = $uri; // It will be '' if $uri was '/' at the beginning!
            $params   = [];   // eg. '/user' => 'user' with no params
        } else {
        // '/' found.
            $function = substr($uri, 0, $index); // eg. 'user'
            if (($paramRaw = substr($uri, $index + 1)) === FALSE) {
                // @TODO: need test!
                $params = []; // eg. '/user/' => 'user/' with no params
            } else {
                // At least one param.
                $params = explode('/', substr($uri, $index + 1)); // eg. ['page', '12']
            }
        }
        if ($function === '') {
            $function = 'index'; // $uri was '/' at the beginning.
        }
        Kit::log([__METHOD__, ['function' => $function, 'params' => $params]]);
        return count($params) ? [$function, $params] : $function;
    }

    /**
     * @param string $description
     * @return string
     */
    private function getPattern($description)
    {
        foreach ([
                '(all)' => '(.+?)',
                '(any)' => '([^/]+?)',
                '(num)' => '([0-9]+?)',
            ] as $key => $value) {
            $description = str_replace($key, $value, $description);
        }
        return '@^' . $description . '$@';
    }

    /**
     * @param array $var
     */
    private function merge($vars)
    {
        // The only place where $this->params is updated.
        $this->params = array_merge($this->params, $vars);
    }

    private function pop()
    {
        // The last of three places where $this->uri is assigned.
        // The last of two places where $this->uris is updated.
        $this->uri = array_pop($this->uris);
    }

    /**
     * If $description IS a prefix of $this->uri, then pushes $this->uri into $this->uris,
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
        $length = strlen($description);
        if (substr($this->uri, 0, $length) !== $description) {
        // $description IS NOT a prefix of $this->uri.
            return FALSE;
        } else {
        // $description IS a prefix of $this->uri, eg. '/about/join/whatever'.
            // The first of two places where $this->uris is updated.
            $this->uris[] = $this->uri;
            // The second of three places where $this->uri is assigned.
            $this->uri = (
                ($uri = substr($this->uri, $length)) === FALSE ? '/' : $uri
            );
            return TRUE;
        }
    }
}