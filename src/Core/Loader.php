<?php

namespace Ilex\Core;

use ReflectionClass;
use \Ilex\Lib\Container;
// use \Ilex\Lib\Kit;

/**
 * Class Loader
 * The class in charge of loading app interblocks.
 * @package Ilex\Core
 * 
 * @property private static \Ilex\Lib\Container $container
 * 
 * @method public static          initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method public static string   ILEXPATH()
 * @method public static string   APPPATH()
 * @method public static string   RUNTIMEPATH()
 * @method public static string   getHandlerFromPath(string $path)
 * @method public static \MongoDB db()
 * @method public static object   controller(string $path, array $params = [])
 * @method public static object   model(string $path, array $params = [])
 * @method public static boolean  isControllerLoaded(string $path)
 * @method public static boolean  isModelLoaded(string $path)
 *
 * @method private static boolean        isLoadedWithBase(string $path, string $type)
 * @method private static object         loadWithBase(string $path, string $type, array $params = [])
 * @method private static string|boolean load(string $path, string $type)
 * @method private static object         createInstance(string $class, array $params)
 * @method private static boolean        has(mixed $k)
 * @method private static mixed          get(mixed $k)
 * @method private static mixed          set(mixed $k, mixed $v)
 * @method private static mixed          setSet(mixed $k, mixed $kk, mixed $v)
 */
class Loader
{
    // Structure: type('Controller'|'Model') => path(eg. 'sys/Input') => class object
    //         or 'ILEXPATH'|'APPPATH'|'RUNTIMEPATH' => string
    private static $container;

    /**
     * @param string $ILEXPATH
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH)
    {
        self::$container = new Container();
        self::set('ILEXPATH',    $ILEXPATH);
        self::set('APPPATH',     $APPPATH);
        self::set('RUNTIMEPATH', $RUNTIMEPATH);

        self::set('Controller', new Container());
        self::set('Model',      new Container());
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
    public static function APPPATH()
    {
        return self::get('APPPATH');
    }

    /**
     * @return string
     */
    public static function RUNTIMEPATH()
    {
        return self::get('RUNTIMEPATH');
    }

    /**
     * Extracts handler name from path.
     * eg. 'sys/Input' => 'Input'
     * @param string $path
     * @return string
     */
    public static function getHandlerFromPath($path)
    {
        $handler = strrchr($path, '/');
        return $handler === FALSE ? $path : substr($handler, 1);
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
                'username'          => SVR_MONGO_USER,
                'password'          => SVR_MONGO_PASS,
                'db'                => SVR_MONGO_DB,
                'connectTimeoutMS'  => SVR_MONGO_TIMEOUT
            ]);
            return self::set('db', $mongo->selectDB(SVR_MONGO_DB));
        }
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
     * @param string $path
     * @param array  $params
     * @return object
     */
    public static function model($path, $params = [])
    {
        return self::loadWithBase($path, 'Model', $params);
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
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string $path eg. 'sys/Input'
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
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'sys/Input', $type = 'Model', 
     *     this function will includes the file : 'ILEXPATH/Base/Model/sys/Input.php', 
     *     and returns '\\Ilex\\Base\\Model\\sys\\Input'
     * @param string $path eg. 'sys/Input'
     * @param string $type eg. 'Model', 'Controller'
     * @return string|boolean
     */
    private static function load($path, $type)
    {
        foreach ([
            'app' => [
                'path' => self::get('APPPATH') . $type . '/' . $path . $type . '.php',
                'name' => 'app\\' . $type . '\\' . $path . $type
            ],
            'ilex' => [
                'path' => self::get('ILEXPATH') . 'Base/' . $type . '/' . $path . '.php',
                'name' => '\\Ilex\\Base\\' . $type . '\\' . str_replace('/', '\\', $path)
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
     * @param mixed $k
     * @return boolean
     */
    private static function has($k)
    {
        return self::$container->has($k);
    }
    /**
     * @param mixed $k
     * @return mixed
     */
    private static function get($k)
    {
        return self::$container->get($k);
    }

    /**
     * @param mixed $k
     * @param mixed $v
     * @return mixed
     */
    private static function set($k, $v)
    {
        return self::$container->set($k, $v);
    }

    /**
     * @param mixed $k
     * @param mixed $kk
     * @param mixed $v
     * @return mixed
     */
    private static function setSet($k, $kk, $v)
    {
        // If the existence is not guaranteed, it will throw an exception.
        return self::$container->get($k)->set($kk, $v);
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