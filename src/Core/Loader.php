<?php

namespace Ilex\Core;

use ReflectionClass;
// use \Ilex\Lib\Kit;

/**
 * Class Loader
 * The class in charge of loading app interblocks.
 * @package Ilex\Core
 * 
 * @property private static array $container
 * 
 * @method public  static                initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method public  static string         ILEXPATH()
 * @method public  static string         APPPATH()
 * @method public  static string         RUNTIMEPATH()
 * @method public  static string         getHandlerFromPath(string $path)
 * @method public  static \MongoDB       db()
 * @method public  static object         controller(string $path, array $params = [])
 * @method public  static object         model(string $path, array $params = [])
 * @method public  static boolean        isControllerLoaded(string $path)
 * @method public  static boolean        isModelLoaded(string $path)
 * @method private static boolean        isLoadedWithBase(string $path, string $type)
 * @method private static object         loadWithBase(string $path, string $type, array $params = [])
 * @method private static string|boolean load(string $path, string $type)
 * @method private static object         createInstance(string $class, array $params)
 * @method private static boolean        has(mixed $k)
 * @method private static mixed          get(mixed $k)
 * @method private static mixed          let(mixed $k, mixed $v)
 * @method private static mixed          letTo(mixed $k, mixed $kk, mixed $v)
 */
class Loader
{
    /**
     * @todo: use \Ilex\Lib\Container
     * structure: type('Controller'|'Model') => path(eg. 'hw/Brain') => class object
     */
    private static $container = [];

    /**
     * @param string $ILEXPATH
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     */
    public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH)
    {
        self::let('ILEXPATH',    $ILEXPATH);
        self::let('APPPATH',     $APPPATH);
        self::let('RUNTIMEPATH', $RUNTIMEPATH);

        self::let('Controller', []); // @todo: use \Ilex\Lib\Container
        self::let('Model',      []); // @todo: use \Ilex\Lib\Container
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
     * eg. 'hw/Brain' => 'Brain'
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
            return self::let('db', $mongo->selectDB(SVR_MONGO_DB));
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
     * @todo private?
     * @param string $path
     * @param string $type
     * @return boolean
     */
    private static function isLoadedWithBase($path, $type)
    {
        $typeEntities = self::get($type);
        return isset($typeEntities[$path]);
    }

     /**
      * @todo private?
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string $path eg. 'hw/Brain'
      * @param string $type eg. 'Model', 'Controller'
      * @param array  $params
      * @return object
      */
    private static function loadWithBase($path, $type, $params = [])
    {
        $typeEntities = self::get($type); // @todo: if NOT isset($container[$type])?
        if (isset($typeEntities[$path])) {
            return $typeEntities[$path];
        } else {
            // @todo: static:: or self:: ?
            $className = self::load($path, $type);
            if ($className === FALSE) {
                throw new \Exception(ucfirst($type) . ' ' . $path . ' not found.');
            }
            $class = self::createInstance($className, $params); // @todo: what?
            return self::letTo($type, $path, $class);
        }
    }


    /**
     * @todo private?
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'hw/Brain', $type = 'Model', 
     *     this function will includes 'APPPATH/Model/hw/BrainModel.php', 
     *     and returns 'BrainModel'
     * @param string $path eg. 'hw/Brain' ?
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
                // @todo: Be sure that it is definitely Model? No suffix: $type?
                'name' => '\\Ilex\\Base\\' . $type . '\\' . str_replace('/', '\\', $path)
            ]
        ] as $item) {
            if (file_exists($item['path'])) {
                include($item['path']);
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
        return isset(self::$container[$k]);
    }
    /**
     * @param mixed $k
     * @return mixed
     */
    private static function get($k)
    {
        return self::$container[$k];
    }

    /**
     * @param mixed $k
     * @param mixed $v
     * @return mixed
     */
    private static function let($k, $v)
    {
        return self::$container[$k] = $v;
    }

    /**
     * @param mixed $k
     * @param mixed $kk
     * @param mixed $v
     * @return mixed
     */
    private static function letTo($k, $kk, $v)
    {
        return self::$container[$k][$kk] = $v;
    }
}