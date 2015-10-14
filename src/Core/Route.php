<?php

namespace Ilex\Core;

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class Route
 * The class in charge of routing requests.
 * @package Ilex\Core
 * 
 * @property private string  $method
 * @property private string  $uri
 * @property private array   $uris
 * @property private array   $params
 * @property private boolean $settled
 * @property private boolean $cancelled
 * @property private         $result
 * 
 * @method public               __construct(string $method, string $uri)
 * @method public  string       __string()
 * @method public               __call(string $name, array $arguments)
 * @method public               controller(string $description, string $handler)
 * @method public               group(string $description, callable $handler)
 * @method public  mixed        result()
 * @method public  boolean      back()
 * @method private boolean      fitGeneral(string $description, mixed $handler, string $function = NULL)
 * @method private string       getPattern(string $description)
 * @method private              merge(array $vars)
 * @method private boolean      fitController(string $description, string $handler)
 * @method private array|string getFunction(string $uri)
 * @method private boolean      fitGroup(string $description, callable $handler)
 * @method private boolean      getRestURI(string $description)
 * @method private              end(mixed $result)
 * @method private              pop()
 * 
 * Methods derived from __call():
 * @method public boolean any   (string $description, mixed $handler, string $function = NULL)
 * @method public boolean get   (string $description, mixed $handler, string $function = NULL)
 * @method public boolean post  (string $description, mixed $handler, string $function = NULL)
 * @method public boolean put   (string $description, mixed $handler, string $function = NULL)
 * @method public boolean delete(string $description, mixed $handler, string $function = NULL)
 */
class Route
{
    private $method;            // eg. 'GET' | 'POST'. Lower or upper case?
    private $uri;
    private $uris      = [];
    private $params    = [];
    private $settled   = FALSE; // @todo: what?
    private $cancelled = FALSE; // @todo: what?
    private $result    = NULL;

    /**
     * @param string $method eg. 'GET' | 'POST' | 'PUT'
     * @param string $uri
     */
    public function __construct($method, $uri)
    {
        // the only place where $method is assigned
        $this->method = $method;
        // the first of three places where $this->uri is assigned
        $this->uri    = $uri;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result  = PHP_EOL . '\Route {' . PHP_EOL;
        $result .= "\tsettled   : " . Kit::toString($this->settled)   . PHP_EOL;
        $result .= "\tcancelled : " . Kit::toString($this->cancelled) . PHP_EOL;
        $result .= "\tmethod    : " . Kit::toString($this->method)    . PHP_EOL;
        $result .= "\turi       : " . Kit::toString($this->uri)       . PHP_EOL;
        $result .= "\turis      : " . Kit::toString($this->uris)      . PHP_EOL;
        $result .= "\tparams    : " . Kit::toString($this->params)    . PHP_EOL;
        $result .= "\tresult    : " . Kit::toString($this->result)    . PHP_EOL;
        $result .= '}';
        return $result;
    }

    /**
     * Checks the method and then calls self::fitGeneral().
     * @param string $name      eg. 'get' | 'post' | 'any' ?
     * @param array  $arguments eg. ['/project/(num)', 'Project', 'view']
     */
    public function __call($name, $arguments)
    {
        if (!$this->settled
            AND (strtoupper($name) === strtoupper($this->method)
                OR strtoupper($name) === 'ANY')) {
            Kit::log([__METHOD__, [
                'settled' => $this->settled,
                'method'  => $this->method,
                'name'    => $name,
                'args'    => $arguments
            ]]);
            Kit::log([__METHOD__, 'call fitGeneral'], FALSE);
            call_user_func_array([$this, 'fitGeneral'], $arguments);
        }
    }

    /**
     * Try to fit controller route.
     * @param string $description eg. '/about'
     * @param string $handler     eg. 'About'
     */
    public function controller($description, $handler)
    {
        if (!$this->settled) {
            Kit::log([__METHOD__, [
                'settled' => $this->settled,
                'desc'    => $description,
                'handler' => $handler
            ]]);
            Kit::log([__METHOD__, 'call fitController'], FALSE);
            $this->fitController($description, $handler);
        }
    }

    /**
     * Try to fit group route.
     * @todo Group routes should implemented in order!!!
     * @param string   $description eg. '/whatever'
     * @param callable $handler     eg. an anonymous function usually with an argument: $Route
     */
    public function group($description, $handler)
    {
        if (!$this->settled) {
            Kit::log([__METHOD__, [
                'settled' => $this->settled,
                'desc'    => $description,
                'handler' => $handler
            ]]);
            Kit::log([__METHOD__, 'call fitGroup'], FALSE);
            $this->fitGroup($description, $handler);
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
     * @todo what?
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
            $this->pop();
            $this->cancelled = TRUE;
            Kit::log([__METHOD__, 'pop()', 'cancelled = TRUE', 'return TRUE'], FALSE);
            return TRUE;
        }
    }

    /**
     * Extracts params and handles the request,
     * by choosing the appropriate handler,
     * and calling the appropriate method
     * (calling `index` method if $function is NOT defined),
     * if $description CAN fit $this->uri.
     * @param string $description eg. '/project/(num)', '/(num)', '/', '/user/(any)', '(all)'
     * @param mixed  $handler     eg. 'Project',        $this,    an anonymous function
     * @param string $function    eg. 'view'
     * @return boolean
     */
    private function fitGeneral($description, $handler, $function = NULL)
    {
        /**
         * eg. $description  : '/project/(num)' => '/project/([0-9]+?)'
         *     $this->uri    : 'http://www.test.com/project/12' or '/project/12'?
         *     $this->params : []
         *     $matches      : ['/project/12', '12']
         */
        Kit::log([__METHOD__, [
            'desc'    => $description,
            'handler' => $handler,
            'func'    => $function
        ]]);
        Kit::log([__METHOD__, ['this' => $this]]);
        if (preg_match(self::getPattern($description), $this->uri, $matches)) {
            unset($matches[0]);
            $this->merge($matches); // $this->params updated
            Kit::log([__METHOD__, 'after merge', ['params' => $this->params]]);
            // eg. $this->params : ['12']
            
            if (is_string($handler) OR !($handler instanceof \Closure)) {
            // $handler is a string or is NOT an anonymous function, i.e., an instance
                Kit::log([__METHOD__, '$handler is a string or is NOT an anonymous function, i.e., an instance'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'handler'  => is_string($handler) ? Loader::controller($handler) : $handler,
                    'function' => is_null($function) ? 'index' : $function,
                    'params'   => $this->params
                ]]);
                // @todo: definitely Loader::controller()? not Loader::model()?
                $this->end(
                    call_user_func_array([
                        is_string($handler) ? Loader::controller($handler) : $handler,
                        is_null($function) ? 'index' : $function // default: `index` method!
                    ], $this->params)
                );
            } elseif (is_callable($handler)) {
            // $handler is an anonymous function
                Kit::log([__METHOD__, '$handler is an anonymous function'], FALSE);
                Kit::log([__METHOD__, 'call end', [
                    'handler' => $handler,
                    'params'  => $this->params
                ]]);
                $this->end(
                    call_user_func_array($handler, $this->params)
                );
            }
            Kit::log([__METHOD__, 'return TRUE'], FALSE);
            return TRUE;
        } else {
            // CAN NOT FIT!
            Kit::log([__METHOD__, 'return FALSE', 'CAN NOT FIT!'], FALSE);
            return FALSE;
        }
    }

    /**
     * @param string $description
     * @return string
     */
    private function getPattern($description)
    {
        foreach ([
                '(any)' => '([^/]+?)',
                '(num)' => '([0-9]+?)',
                '(all)' => '(.+?)'
            ] as $k => $v) {
            $description = str_replace($k, $v, $description);
        }
        return '@^' . $description . '$@';
    }

    /**
     * @param array $var
     */
    private function merge($vars)
    {
        // the only place where $this->params is updated
        $this->params = array_merge($this->params, $vars);
    }

    /**
     * Extracts function and params and handles the request,
     * by calling the appropriate method
     * (calling `resolve` method if method $function does NOT exist),
     * if $description and $handler CAN fit $this->uri.
     * @param string $description eg. '/about'
     * @param string $handler     eg. 'About'
     * @return boolean
     */
    private function fitController($description, $handler)
    {
        /**
         * eg. $this->uri    : '/about/join/whatever'
         *     $description  : '/about' 
         *     $handler      : 'About' 
         *     $this->method : 'POST' 
         */
        if (!$this->getRestURI($description)) {
        // $description is NOT a prefix of $this->uri, CAN NOT FIT!
            return FALSE;
        }
        // eg. $this->uri  : '/join/whatever'
        // eg. $this->uris : ['/about/join/whatever']
        $function = self::getFunction($this->uri); // eg. ['join', ['whatever']]
        if (is_array($function)) {
            $function = $function[0]; // eg. 'join'
            $params = $function[1];   // eg. ['whatever']
        } else {
            $params = [];
        }
        $controller = Loader::controller($handler); // eg. \AboutController

        // @todo: possibly conflict!?
        if (method_exists($controller, $this->method . $function)) {
            // eg. AboutController::POSTjoin().
            // @todo: Should strtolower()?
            $fn = $this->method . $function; // eg. 'POSTjoin'
        } elseif (method_exists($controller, $function)) {
            // eg. AboutController::join()
            $fn = $function; // eg. 'join'
        } elseif (method_exists($controller, 'resolve')) {
            // eg. AboutController::resolve()
            $fn = 'resolve';
            $params = [$this];
        } else {
            // CAN NOT FIT! Rollback!
            $this->pop();
            // eg. $this->uri  : '/about/join/whatever'
            // eg. $this->uris : []
            return FALSE;
        }
        // var_dump('hello');
        // var_dump($this->uri);
        // var_dump($function);
        // var_dump($params);
        // var_dump($fn);
        // var_dump('word');
        // var_dump($params instanceof object);
        $this->end(call_user_func_array([$controller, $fn], $params));
        return TRUE;
    }

    /**
     * Returns [$function, $params] if parameters found, else returns $function.
     * @param string $uri   eg. '/mission/page/12'
     * @return array|string eg. ['mission', ['page', '12']]
     */
    private function getFunction($uri)
    {
        // Search for the first '/' in $uri.
        // eg. '/mission/page/12'
        $index = strpos($uri, '/'); // eg. 0
        if ($index === 0) {
        // $uri begins with '/'.
            if (($uri = substr($uri, 1)) === FALSE) {
                // Fails to exclude '/' because $uri is '/'.
                $uri = '';
                $index = FALSE;
            } else {
                // Now the first '/' is excluded. Search for the second '/' in $uri.
                // eg. 'mission/page/12'
                $index = strpos($uri, '/'); // eg. 7
            }
        }
        if ($index === FALSE) {
        // '/' NOT found
            $function = $uri; // It will be '' if $uri was '/' at the beginning!
            $params = []; // eg. '/mission' => 'mission' with no params
        } else {
        // '/' found
            $function = substr($uri, 0, $index); // eg. 'mission'
            if (($paramRaw = substr($uri, $index + 1)) === FALSE) {
                $params = []; // eg. '/mission/' => 'mission/' with no params
            } else {
                // at least one param
                $params = explode('/', substr($uri, $index + 1)); // eg. ['page', '12']
            }
        }
        if ($function === '') {
            $function = 'index'; // $uri was '/' at the beginning
        }
        // eg. ['mission', ['page', '12']]
        return count($params) ? [$function, $params] : $function;
    }

    /**
     * @todo what?
     * @param string   $description eg. '/about' 
     * @param callable $handler     eg. an anonymous function usually with an argument: $Route 
     * @return boolean
     */
    private function fitGroup($description, $handler)
    {
        /**
         * eg. $this->uri    : '/about/join/whatever'
         *     $description  : '/about' 
         *     $handler      : 'About' 
         *     $this->method : 'POST' 
         */
        if (!$this->getRestURI($description)) {
        // $description is NOT a prefix of $this->uri, CAN NOT FIT!
            return FALSE;
        }
        // eg. $this->uri  : '/join/whatever'
        // eg. $this->uris : ['/about/join/whatever']
        
        $this->end(call_user_func($handler, $this));
        return TRUE;
    }

    /**
     * If $description is a prefix of $this->uri,
     * then pushes $this->uri into $this->uris,
     * and extracts the rest uri from $this->uri,
     * and updates it and returns TRUE,
     * else returns FALSE.
     * '/about/join/whatever' => '/join/whatever'
     * @param string $description eg. '/about'
     * @return boolean
     */
    private function getRestURI($description)
    {
        $length = strlen($description);
        if (substr($this->uri, 0, $length) !== $description) {
        // $description is NOT a prefix of $this->uri
            return FALSE;
        } else {
        // $description is a prefix of $this->uri, eg. '/about/join/whatever'
            // the second of two places where $this->uris is updated
            $this->uris[] = $this->uri;
            // the third of three places where $this->uri is assigned
            // extracts the rest uri, eg. '/join/whatever'
            $this->uri = (
                ($uri = substr($this->uri, $length)) === FALSE ? '/' : $uri
            );
            return TRUE;
        }
    }

    /**
     * @todo what?
     * @param mixed $result
     */
    private function end($result)
    {
        Kit::log([__METHOD__, ['result' => $result]]);
        if ($this->cancelled) {
            $this->cancelled = FALSE;
        } else {
            $this->settled = TRUE;
            $this->result = $result;
        }
        Kit::log([__METHOD__, ['this' => $this]]);
    }

    private function pop()
    {
        // the second of three places where $this->uri is assigned
        // the first of two places where $this->uris is updated
        $this->uri = array_pop($this->uris);
    }
}