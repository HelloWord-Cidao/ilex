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
 * @method final public static string   APPPATH()
 * @method final public static string   APPNAME()
 * @method final public static string   ILEXPATH()
 * @method final public static string   RUNTIMEPATH()
 * @method final public static object   controller(string $path, array $param_list = [], boolean $with_construct = TRUE)
 * @method final public static \MongoDB db()
 * @method final public static string   getHandlerFromPath(string $path, string $delimiter = '/')
 * @method final public static string   getHandlerPrefixFromPath(string $path, string $delimiter = '/', array $more_suffix_list = [])
 * @method final public static          initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method final public static boolean  isControllerLoaded(string $path)
 * @method final public static boolean  isModelLoaded(string $path)
 * @method final public static object   model(string $path, array $param_list = [], boolean $with_construct = TRUE)
 *
 * @method final private static object         createInstance(string $class_name, array $param_list, boolean $with_construct)
 * @method final private static mixed          get(mixed $key)
 * @method final private static boolean        has(mixed $key)
 * @method final private static string|boolean includeFile(string $path, string $type)
 * @method final private static boolean        isLoaded(string $path, string $type)
 * @method final private static object         load(string $path, string $type, array $param_list = [], boolean $with_construct)
 * @method final private static mixed          set(mixed $key, mixed $value)
 * @method final private static mixed          setSet(mixed $key, mixed $keyKey, mixed $value)
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
    final public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH, $APPNAME)
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
    final public static function APPPATH()
    {
        return self::get('APPPATH');
    }

    /**
     * @return string
     */
    final public static function APPNAME()
    {
        return self::get('APPNAME');
    }

    /**
     * @return string
     */
    final public static function ILEXPATH()
    {
        return self::get('ILEXPATH');
    }

    /**
     * @return string
     */
    final public static function RUNTIMEPATH()
    {
        return self::get('RUNTIMEPATH');
    }

    /**
     * @return \MongoDB
     */
    final public static function db()
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
     * @param string $path
     * @param array  $param_list
     * @return object
     */
    final public static function controller($path, $param_list = [], $with_construct = TRUE)
    {
        return self::load($path, 'Controller', $param_list, $with_construct);
    }

    /**
     * @param string $path
     * @param array  $param_list
     * @return object
     */
    final public static function model($path, $param_list = [], $with_construct = TRUE)
    {
        return self::load($path, 'Model', $param_list, $with_construct);
    }

     /**
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $container.
      * The function ensures that for each model only one entity is loaded.
      * @param string $path eg. 'System/Input'
      * @param string $type eg. 'Model', 'Controller'
      * @param array  $param_list
      * @return object
      */
    final private static function load($path, $type, $param_list, $with_construct)
    {
        // @todo: If $type is not 'Controller' or 'Model', it will throw an exception.
        $type_entity_list = self::get($type);
        if (TRUE === $type_entity_list->has($path)) {
            return $type_entity_list->get($path);
        } else {
            $class_name = self::includeFile($path, $type);
            if (FALSE === $class_name) {
                throw new \Exception(ucfirst($type) . ' ' . $path . ' not found.');
            }
            $instance = self::createInstance($class_name, $param_list, $with_construct);
            return self::setSet($type, $path, $instance);
        }
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
    final private static function includeFile($path, $type)
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
            if (TRUE === file_exists($item['path'])) {
                // Now include the app class here, then it can be used somewhere else!
                // @todo: should only include once?
                includeFile($item['path']);
                return $item['name'];
            }
        }
        return FALSE;
    }

    /**
     * @param string $class_name
     * @param array  $param_list
     * @return object
     */
    final private static function createInstance($class_name, $param_list, $with_construct)
    {
        $reflection_class = new ReflectionClass($class_name);
        if (TRUE === $with_construct) return $reflection_class->newInstanceArgs($param_list);
        else return $reflection_class->newInstanceWithoutConstructor();
    }

    /**
     * Extracts handler name from path.
     * eg. 'System/Input' => 'Input'
     * @param string $path
     * @param string $delimiter
     * @return string
     */
    final public static function getHandlerFromPath($path, $delimiter = '/')
    {
        $handler = strrchr($path, $delimiter);
        return FALSE === $handler ? $path : substr($handler, 1);
    }

    /**
     * Extracts handler prefix name from path.
     * eg. 'Service/AdminServiceController' => 'Admin'
     * @param string $path
     * @param string $delimiter
     * @param array $more_suffix_list
     * @return string
     */
    final public static function getHandlerPrefixFromPath($path, $delimiter = '/', $more_suffix_list = [])
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
    final public static function isControllerLoaded($path)
    {
        return self::isLoaded($path, 'Controller');
    }

    /**
     * @param string $path
     * @return boolean
     */
    final public static function isModelLoaded($path)
    {
        return self::isLoaded($path, 'Model');
    }

    /**
     * @param string $path
     * @param string $type
     * @return boolean
     */
    final private static function isLoaded($path, $type)
    {
        // @todo: If $type is not 'Controller' or 'Model', it will throw an exception.
        $type_entity_list = self::get($type);
        return $type_entity_list->has($path);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    final private static function get($key)
    {
        return self::$container->get($key);
    }

    /**
     * @param mixed $key
     * @return boolean
     */
    final private static function has($key)
    {
        return self::$container->has($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    final private static function set($key, $value)
    {
        return self::$container->set($key, $value);
    }

    /**
     * @param mixed $key
     * @param mixed $keyKey
     * @param mixed $value
     * @return mixed
     */
    final private static function setSet($key, $keyKey, $value)
    {
        // @todo: If the existence is not guaranteed, it will throw an exception.
        return self::$container->get($key)->set($keyKey, $value);
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