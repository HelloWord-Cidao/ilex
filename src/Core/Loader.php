<?php

namespace Ilex\Core;

use ReflectionClass;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

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
 * @method public static object   controller(string $path, array $params = [])
 * @method public static \MongoDB db()
 * @method public static string   getHandlerFromPath(string $path)
 * @method public static          initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method public static boolean  isControllerLoaded(string $path)
 * @method public static boolean  isModelLoaded(string $path)
 * @method public static object   model(string $path, array $params = [])
 *
 * @method private static object         createInstance(string $class, array $params)
 * @method private static mixed          get(mixed $key)
 * @method private static boolean        has(mixed $key)
 * @method private static boolean        isLoadedWithBase(string $path, string $type)
 * @method private static string|boolean load(string $path, string $type)
 * @method private static object         loadWithBase(string $path, string $type, array $params = [])
 * @method private static mixed          set(mixed $key, mixed $value)
 * @method private static mixed          setSet(mixed $key, mixed $keyKey, mixed $value)
 */
class Loader
{
    // Structure: type('Controller'|'Model') => path(eg. 'System/Input') => class object
    //         or 'ILEXPATH'|'APPPATH'|'RUNTIMEPATH' => string
    private static $container;

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
     * @param string $path
     * @param array  $params
     * @return object
     */
    public static function controller($path, $params = [])
    {
        return self::loadWithBase($path, 'Controller', $params);
    }

    /**
     * @return \MongoDB
     */
    public static function db()
    {
        if (self::has('db')) {
            return self::get('db');
        } else {
            $mongo = new \MongoClient(SVR_MONGO_HOST . ':' . SVR_MONGO_PORT, [
                'username'         => SVR_MONGO_USER,
                'password'         => SVR_MONGO_PASS,
                'db'               => SVR_MONGO_DB,
                'connectTimeoutMS' => SVR_MONGO_TIMEOUT,
            ]);
            return self::set('db', $mongo->selectDB(SVR_MONGO_DB));
        }
    }

    /**
     * Extracts handler name from path.
     * eg. 'System/Input' => 'Input'
     * @param string $path
     * @return string
     */
    public static function getHandlerFromPath($path)
    {
        $handler = strrchr($path, '/');
        return $handler === FALSE ? $path : substr($handler, 1);
    }

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
     * @param string $path
     * @return boolean
     */
    public static function isControllerLoaded($path)
    {
        return self::isLoadedWithBase($path, 'Controller');
    }

    /**
     * @param string $path
     * @return boolean
     */
    public static function isModelLoaded($path)
    {
        return self::isLoadedWithBase($path, 'Model');
    }

    /**
     * @param string $path
     * @param array  $params
     * @return object
     */
    public static function model($path, $params = [])
    {
        return self::loadWithBase($path, 'Model', $params);
    }

    /**
     * @param string $class
     * @param array  $params
     * @return object
     */
    private static function createInstance($class, $params)
    {
        $reflection_class = new ReflectionClass($class);
        return $reflection_class->newInstanceArgs($params);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    private static function get($key)
    {
        return self::$container->get($key);
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
     * @param string $path
     * @param string $type
     * @return boolean
     */
    private static function isLoadedWithBase($path, $type)
    {
        // If $type is not 'Controller' or 'Model', it will throw an exception.
        $typeEntities = self::get($type);
        return $typeEntities->has($path);
    }

    /**
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'System/Input', $type = 'Model', 
     *     this function will includes the file : 'ILEXPATH/Base/Model/System/Input.php', 
     *     and returns '\\Ilex\\Base\\Model\\System\\Input'
     * @param string $path eg. 'System/Input'
     * @param string $type eg. 'Model', 'Controller'
     * @return string|boolean
     */
    private static function load($path, $type)
    {
        foreach ([
            'app' => [
                'name' => '\\' . self::get('APPNAME'). '\\' . $type . '\\' . str_replace('/', '\\', $path) . $type,
                'path' => self::get('APPPATH') . $type . '/' . $path . $type . '.php',
            ],
            'ilex' => [
                'name' => '\\Ilex\\Base\\' . $type . '\\' . str_replace('/', '\\', $path),
                'path' => self::get('ILEXPATH') . 'Base/' . $type . '/' . $path . '.php',
            ]
        ] as $item) {
            if (file_exists($item['path'])) {
                // Now include the app class here, then it can be used somewhere else!
                // @todo: should only include once?
                includeFile($item['path']);
                return $item['name'];
            }
        }
        return FALSE;
    }

     /**
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string $path eg. 'System/Input'
      * @param string $type eg. 'Model', 'Controller'
      * @param array  $params
      * @return object
      */
    private static function loadWithBase($path, $type, $params = [])
    {
        // If $type is not 'Controller' or 'Model', it will throw an exception.
        $typeEntities = self::get($type);
        if ($typeEntities->has($path)) {
            return $typeEntities->get($path);
        } else {
            $className = self::load($path, $type);
            if ($className === FALSE) {
                throw new \Exception(ucfirst($type) . ' ' . $path . ' not found.');
            }
            $instance = self::createInstance($className, $params);
            return self::setSet($type, $path, $instance);
        }
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
        // If the existence is not guaranteed, it will throw an exception.
        return self::$container->get($key)->set($keyKey, $value);
    }
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 */
function includeFile($file)
{
    include $file;
}