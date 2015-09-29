<?php

namespace Ilex\Core;
use ReflectionClass;

/**
 * Class Loader
 * @package Ilex\Core
 */
class Loader
{
    private static $container = array();
    private static function has($k)            { return isset(self::$container[$k]);    }
    private static function get($k)            { return self::$container[$k];           }
    private static function let($k, $v)        { return self::$container[$k]      = $v; }
    private static function letTo($k, $kk, $v) { return self::$container[$k][$kk] = $v; }

    public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH)
    {
        self::let('ILEXPATH', $ILEXPATH);
        self::let('APPPATH', $APPPATH);
        self::let('RUNTIMEPATH', $RUNTIMEPATH);

        self::let('Controller', array());
        self::let('Model', array());
    }

    public static function ILEXPATH()    { return self::get('ILEXPATH');    }
    public static function APPPATH()     { return self::get('APPPATH');     }
    public static function RUNTIMEPATH() { return self::get('RUNTIMEPATH'); }

    /**
     * @return object \MongoDB
     */
    public static function db()
    {
        if (self::has('db')) {
            return self::get('db');
        } else {
            // should include these CONST first? when?
            $mongo = new \MongoClient(SVR_MONGO_HOST . ':' . SVR_MONGO_PORT, array(
                'username'          => SVR_MONGO_USER,
                'password'          => SVR_MONGO_PASS,
                'db'                => SVR_MONGO_DB,
                'connectTimeoutMS'  => SVR_MONGO_TIMEOUT
            ));
            return self::let('db', $mongo->selectDB(SVR_MONGO_DB));
        }
    }

    /**
     * @param string $path
     * @return object
     */
    public static function controller($path) {
        return self::loadWithBase($path, 'Controller');
    }

    /**
     * @param string $path
     * @param array $params
     * @return 
     */
    public static function model($path, $params = array()) {
        return self::loadWithBase($path, 'Model', $params);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public static function isModelLoaded($path) {
        return self::isLoadedWithBase($path, 'Model');
    }

    /**
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
      * Returns a loaded class, if it is not already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string $path
      * @param string $type
      * @param array  $params
      * @return object
      */
    private static function loadWithBase($path, $type, $params = array())
    {
        $typeEntities = self::get($type);
        if (isset($typeEntities[$path])) {
            return $typeEntities[$path];
        } else {
            $className = static::load($path, $type);
            if ($className === FALSE) {
                throw new \Exception(ucfirst($type) . ' ' . $path . ' not found.');
            }
            $class = self::createInstance($className, $params); // @todo what
            return self::letTo($type, $path, $class);
        }
    }


    /**
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'hw/Brain', $type = 'Model', 
     *     this function will includes 'APPPATH/Model/hw/BrainModel.php', 
     *     and returns 'BrainModel'
     * @param string $path etc. 'hw/Brain' ?
     * @param string $type etc. 'config', 'Model', 'Controller', 'view' ?
     * @return string
     */
    private static function load($path, $type)
    {
        foreach (array(
            'app' => array(
                'path' => self::get('APPPATH') . $type . '/' . $path . $type . '.php',
                'name' => self::getHandlerFromPath($path) . $type
            ),
            'ilex' => array(
                'path' => self::get('ILEXPATH') . 'Base/' . $type . '/' . $path . '.php',
                'name' => '\\Ilex\\Base\\Model\\' . str_replace('/', '\\', $path) // @todo definitely Model?
            )
        ) as $item) {
            if (file_exists($item['path'])) {
                include($item['path']);
                return $item['name'];
            }
        }
        return FALSE;
    }

    /**
     * 'hw/Brain' => 'Brain'
     * @return string
     */
    public static function getHandlerFromPath($path)
    {
        $handler = strrchr($path, '/');
        return $handler === FALSE ? $path : substr($handler, 1);
    }

    /**
     * @param string $class
     * @param array $params
     * @return object
     */
    private static function createInstance($class, $params) {
        $reflection_class = new ReflectionClass($class);
        return $reflection_class->newInstanceArgs($params);
    }
}