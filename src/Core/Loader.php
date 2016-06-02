<?php

namespace Ilex\Core;

use \Exception;
use \ReflectionClass;
use \MongoDB;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class Loader
 * The class in charge of loading app interblocks.
 * @package Ilex\Core
 * 
 * @property private static \Ilex\Lib\Container $container
 * 
 * @method public static string   APPPATH()
 * @method public static string   APPNAME()
 * @method public static string   ILEXPATH()
 * @method public static string   RUNTIMEPATH()
 * @method public static object   controller(string $path, array $arg_list = []
 *                                    , boolean $with_instantiate = TRUE)
 * @method public static MongoDB  db()
 * @method public static string   getHandlerFromPath(string $path, string $delimiter = '/')
 * @method public static string   getHandlerPrefixFromPath(string $path, string $delimiter = '/'
 *                                    , array $more_suffix_list = [])
 * @method public static          initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method public static boolean  isControllerLoaded(string $path)
 * @method public static boolean  isModelLoaded(string $path)
 * @method public static object   model(string $path, array $arg_list = []
 *                                    , boolean $with_instantiate = TRUE)
 *
 * @method private static object         createInstance(string $class_name, array $arg_list)
 * @method private static mixed          get(mixed $key)
 * @method private static boolean        has(mixed $key)
 * @method private static string         includeFile(string $path, string $type)
 * @method private static boolean        isLoaded(string $path, string $type)
 * @method private static object         load(string $path, string $type, array $arg_list = []
 *                                           , boolean $with_instantiate)
 * @method private static mixed          set(mixed $key, mixed $value)
 * @method private static mixed          setSet(mixed $key, mixed $keyKey, mixed $value)
 */
final class Loader
{
    // Structure: type ('Controller'|'Model') => path (eg. 'System/Input') => class object
    //         or 'ILEXPATH'|'APPPATH'|'RUNTIMEPATH' => string
    private static $container;

    /**
     * @param string $ILEXPATH
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        self::$container = new Container();
        self::set('ILEXPATH',    $ILEXPATH);
        self::set('APPPATH',     $APPPATH);
        self::set('RUNTIMEPATH', $RUNTIMEPATH);
        self::set('APPNAME',     $APPNAME);

        self::set('Controller', new Container());
        self::set('Model',      new Container());
    }

    /**
     * @return string
     */
    public static function APPPATH()
    {
        return self::get('APPPATH');
    }

    /**
     * @return string
     */
    public static function APPNAME()
    {
        return self::get('APPNAME');
    }

    /**
     * @return string
     */
    public static function ILEXPATH()
    {
        return self::get('ILEXPATH');
    }

    /**
     * @return string
     */
    public static function RUNTIMEPATH()
    {
        return self::get('RUNTIMEPATH');
    }

    /**
     * @return MongoDB
     */
    public static function db()
    {
        if (TRUE === self::has('db')) {
            return self::get('db');
        } else {
            $mongo_client = new \MongoClient(SVR_MONGO_HOST . ':' . SVR_MONGO_PORT, [
                'username'         => SVR_MONGO_USER,
                'password'         => SVR_MONGO_PASS,
                'db'               => SVR_MONGO_DB,
                'connectTimeoutMS' => SVR_MONGO_TIMEOUT,
            ]);
            return self::set('db', $mongo_client->selectDB(SVR_MONGO_DB));
        }
    }

    /**
     * @param string  $path
     * @param array   $arg_list
     * @param boolean $with_instantiate
     * @return object
     */
    public static function controller($path, $arg_list = [], $with_instantiate = TRUE)
    {
        return self::load($path, 'Controller', $arg_list, $with_instantiate);
    }

    /**
     * @param string  $path
     * @param array   $arg_list
     * @param boolean $with_instantiate
     * @return object
     */
    public static function model($path, $arg_list = [], $with_instantiate = TRUE)
    {
        return self::load($path, 'Model', $arg_list, $with_instantiate);
    }

     /**
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string  $path eg. 'System/Input'
      * @param string  $type eg. 'Model', 'Controller'
      * @param array   $arg_list
      * @param boolean $with_instantiate
      * @return object
      */
    private static function load($path, $type, $arg_list, $with_instantiate)
    {
        if (FALSE === in_array($type, [ 'Controller', 'Model' ]))
            throw new UserException("Invalid \$type($type).");
        $instance_container = self::get($type);
        if (TRUE === $instance_container->has($path)) {
            return $instance_container->get($path);
        } else {
            try {
                $class_name = self::includeFile($path, $type);
            } catch (Exception $e) {
                throw new UserException(ucfirst($type) . ' ' . $path . ' not found.', NULL, $e);
            }
            if (TRUE === $with_instantiate)
                $instance = self::createInstance($class_name, $arg_list);
            else $instance = TRUE;
            return self::setSet($type, $path, $instance);
        }
    }

    /**
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'System/Input', $type = 'Model', 
     *     this function will includes the file : 'ILEXPATH/Model/System/Input.php', 
     *     and returns '\\Ilex\\Base\\Model\\System\\Input'
     * @param string $path eg. 'System/Input'
     * @param string $type eg. 'Model', 'Controller'
     * @return string
     */
    private static function includeFile($path, $type)
    {
        $item_list = [
            'app' => [
                'name' => '\\' . self::get('APPNAME'). '\\' . $type . '\\'
                    . str_replace('/', '\\', $path),
                'path' => self::get('APPPATH') . $type . '/' . $path . '.php',
            ],
            'ilex' => [
                'name' => '\\Ilex\\Base\\' . $type . '\\' . str_replace('/', '\\', $path),
                'path' => self::get('ILEXPATH') . $type . '/' . $path . '.php',
            ]
        ];
        foreach ($item_list as $item) {
            if (TRUE === file_exists($item['path'])) {
                // Now include the app class here, then it can be used somewhere else!
                // @todo: should only include once?
                includeFile($item['path']);
                return $item['name'];
            }
        }
        throw new UserException("File($type/$path.php) not found.");
    }

    /**
     * @param string $class_name
     * @param array  $arg_list
     * @return object
     */
    private static function createInstance($class_name, $arg_list)
    {
        $reflection_class = new ReflectionClass($class_name);
        // if (TRUE === $with_instantiate)
            return $reflection_class->newInstanceArgs($arg_list);
        // else return $reflection_class->newInstanceWithoutConstructor();
    }

    /**
     * Extracts handler name from path.
     * eg. 'System/Input' => 'Input'
     * @param string $path
     * @param string $delimiter
     * @return string
     */
    public static function getHandlerFromPath($path, $delimiter = '/')
    {
        $handler = strrchr($path, $delimiter);
        return FALSE === $handler ? $path : substr($handler, 1);
    }

    /**
     * Extracts handler prefix name from path.
     * eg. 'Service/AdminServiceController' => 'Admin'
     * @param string $path
     * @param string $delimiter
     * @param array  $more_suffix_list
     * @return string
     */
    public static function getHandlerPrefixFromPath($path, $delimiter = '/', $more_suffix_list = [])
    {
        $suffix_list     = array_merge(['Controller', 'Model'], $more_suffix_list);
        $handler         = self::getHandlerFromPath($path, $delimiter);
        $title_word_list = Kit::separateTitleWords($handler);
        while (count($title_word_list) > 0) {
            if (TRUE === in_array(Kit::last($title_word_list), $suffix_list)) {
                array_pop($title_word_list);
            } else break;
        }
        if (0 === count($title_word_list)) return '';
        return join($title_word_list);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public static function isControllerLoaded($path)
    {
        return self::isLoaded($path, 'Controller');
    }

    /**
     * @param string $path
     * @return boolean
     */
    public static function isModelLoaded($path)
    {
        return self::isLoaded($path, 'Model');
    }

    /**
     * @param string $path
     * @param string $type
     * @return boolean
     */
    private static function isLoaded($path, $type)
    {
        if (FALSE === in_array($type, [ 'Controller', 'Model' ]))
            throw new UserException("Invalid \$type($type).");
        $instance_container = self::get($type);
        return $instance_container->has($path);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    private static function set($key, $value)
    {
        return self::$container->set($key, $value);
    }

    /**
     * @param mixed $key
     * @param mixed $keyKey
     * @param mixed $value
     * @return mixed
     */
    private static function setSet($key, $keyKey, $value)
    {
        if (FALSE === self::$container->has($key))
            throw new UserException('self::$container has no $key.');
        return self::$container->get($key)->set($keyKey, $value);
    }

    /**
     * @param mixed $key
     * @return boolean
     */
    private static function has($key)
    {
        return self::$container->has($key);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    private static function get($key)
    {
        return self::$container->get($key);
    }
}

/**
 * Scope isolated include.
 * Prevents access to $this/self from included files.
 * @param string $file
 */
function includeFile($file)
{
    include $file;
}