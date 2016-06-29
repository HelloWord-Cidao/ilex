<?php

namespace Ilex\Core;

use \Exception;
use \ReflectionClass;
use \MongoDB;
use \MongoClient;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class Loader
 * The class in charge of loading app interblocks.
 * @package Ilex\Core
 * 
 * @property private static \Ilex\Lib\Container $instances
 * 
 * @method public static string  APPPATH()
 * @method public static string  APPNAME()
 * @method public static string  ILEXPATH()
 * @method public static string  RUNTIMEPATH()
 * @method public static object  controller(string $path, array $arg_list = []
 *                                   , boolean $with_instantiate = TRUE)
 * @method public static MongoDB db()
 * @method public static string  getHandlerFromPath(string $path, string $delimiter = '/')
 * @method public static string  getHandlerPrefixFromPath(string $path, string $delimiter = '\\')
 * @method public static string  getHandlerSuffixFromPath(string $path, string $delimiter = '\\')
 * @method public static         initialize(string $ILEXPATH, string $APPPATH, string $RUNTIMEPATH)
 * @method public static object  model(string $path, array $arg_list = []
 *                                   , boolean $with_instantiate = TRUE)
 *
 * @method private static object  createInstance(string $class_name, array $arg_list)
 * @method private static mixed   get(mixed $key)
 * @method private static boolean has(mixed $key)
 * @method private static string  includeFile(string $path, string $type)
 * @method private static object  load(string $path, string $type, array $arg_list = []
 *                                    , boolean $with_instantiate)
 * @method private static mixed   set(mixed $key, mixed $value)
 */
final class Loader
{
    private static $instances;

    const I_ILEXPATH    = 'ILEXPATH';
    const I_APPPATH     = 'APPPATH';
    const I_RUNTIMEPATH = 'RUNTIMEPATH';
    const I_APPNAME     = 'APPNAME';
    const I_MONGODB     = 'MONGODB';

    private static $handler_suffix_list = [
        'Service',
        'Config',
        'Data',
        'Core',
        'Log',
        'Collection',
        'Wrapper',
        'Entity',
        'EntityBulk',
    ];

    /**
     * @param string $ILEXPATH
     * @param string $APPPATH
     * @param string $RUNTIMEPATH
     * @param string $APPNAME
     */
    public static function initialize($ILEXPATH, $APPPATH, $RUNTIMEPATH, $APPNAME)
    {
        self::$instances = new Container();
        self::set(self::I_ILEXPATH, $ILEXPATH);
        self::set(self::I_APPPATH, $APPPATH);
        self::set(self::I_RUNTIMEPATH, $RUNTIMEPATH);
        self::set(self::I_APPNAME, $APPNAME);
    }

    /**
     * @return string
     */
    public static function ILEXPATH()
    {
        return self::get(self::I_ILEXPATH);
    }

    /**
     * @return string
     */
    public static function APPPATH()
    {
        return self::get(self::I_APPPATH);
    }

    /**
     * @return string
     */
    public static function RUNTIMEPATH()
    {
        return self::get(self::I_RUNTIMEPATH);
    }

    /**
     * @return string
     */
    public static function APPNAME()
    {
        return self::get(self::I_APPNAME);
    }

    /**
     * @return MongoDB
     */
    public static function loadMongoDB()
    {
        if (TRUE === self::has(self::I_MONGODB)) {
            return self::get(self::I_MONGODB);
        } else {
            $mongo_client = new MongoClient(SVR_MONGO_HOST . ':' . SVR_MONGO_PORT, [
                'username'         => SVR_MONGO_USER,
                'password'         => SVR_MONGO_PASS,
                'db'               => SVR_MONGO_DB,
                'connectTimeoutMS' => SVR_MONGO_TIMEOUT,
            ]);
            return self::set(self::I_MONGODB, $mongo_client->selectDB(SVR_MONGO_DB));
        }
    }

    public static function loadService($path)
    {
        return self::loadController("Service/${path}Service");
    }

    /**
     * @param string  $path
     * @param boolean $with_instantiate
     * @param array   $arg_list
     * @return object
     */
    private static function loadController($path, $with_instantiate = TRUE, $arg_list = [])
    {
        return self::load("Controller/$path", $with_instantiate, $arg_list);
    }

    public static function loadInput()
    {
        return self::loadModel('System/Input');
    }

    public static function loadConfig($path)
    {
        return self::loadModel("Config/${path}Config");
    }

    public static function loadData($path)
    {
        return self::loadModel("Data/${path}Data");
    }

    public static function loadLog($path)
    {
        return self::loadModel("Log/${path}Log");
    }

    public static function loadCore($path, $arg_list = [])
    {
        return self::loadModel("Core/${path}Core", TRUE, $arg_list);
    }

    public static function loadCollection($path, $arg_list = [])
    {
        return self::loadModel("Collection/${path}Collection", TRUE, $arg_list);
    }

    /**
     * @param string  $path
     * @param boolean $with_instantiate
     * @param array   $arg_list
     * @return object
     */
    public static function loadModel($path, $with_instantiate = TRUE, $arg_list = [])
    {
        return self::load("Model/$path", $with_instantiate, $arg_list);
    }

     /**
      * Returns a loaded class, if it is NOT already loaded, 
      * then load it and save it into $instances.
      * The function ensures that for each class only one instance is loaded.
      * @param string  $path eg. 'System/Input'
      * @param boolean $with_instantiate
      * @param array   $arg_list
      * @return object
      */
    private static function load($path, $with_instantiate, $arg_list)
    {
        // var_dump([$path, self::has($path)]);
        if (TRUE === self::has($path)) {
            return self::get($path);
        } else {
            $class_name = self::includeFile($path);
            $instance   = self::createInstance($class_name, $with_instantiate, $arg_list);
            return self::set($path, $instance);
        }
    }

    /**
     * @param string  $class_name
     * @param boolean $with_instantiate
     * @param array   $arg_list
     * @return object
     */
    private static function createInstance($class_name, $with_instantiate, $arg_list)
    {
        $reflection_class = new ReflectionClass($class_name);
        if (TRUE === $with_instantiate)
            return $reflection_class->newInstanceArgs($arg_list);
        else return $reflection_class->newInstanceWithoutConstructor();
    }

    public static function includeEntity($path)
    {
        $class_name = self::includeFile("Model/Entity/${path}Entity");
        // $instance   = self::createInstance($class_name, TRUE, []);
        // return $instance;
        return $class_name; // full name
    }

    public static function includeEntityBulk($path)
    {
        $class_name = self::includeFile("Model/Bulk/${path}EntityBulk");
        // $instance   = self::createInstance($class_name, TRUE, []);
        // return $instance;
        return $class_name; // full name
    }

    public static function includeCore($path)
    {
        $class_name = self::includeFile("Model/Core/${path}Core");
        // $instance   = self::createInstance($class_name, TRUE, []);
        // return $instance;
        return $class_name; // full name
    }

    /**
     * Includes package and return its name, returns FALSE if fails.
     * Try APPPATH first and then ILEXPATH.
     * eg. $path = 'Model/System/Input'
     *     this function will includes the file : 'ILEXPATH/Model/System/Input.php', 
     *     and returns '\\Ilex\\Base\\Model\\System\\Input'
     * @param string $path eg. 'System/Input'
     * @return string
     */
    private static function includeFile($path)
    {
        $item_list = [
            'app' => [
                'name' => '\\' . self::get('APPNAME') . '\\' . str_replace('/', '\\', $path),
                'path' => self::get('APPPATH') . $path . '.php',
            ],
            'ilex' => [
                'name' => '\\Ilex\\Base\\' . str_replace('/', '\\', $path),
                'path' => self::get('ILEXPATH') . $path . '.php',
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
     * Extracts handler prefix name from path.
     * eg. 'Service/AdminServiceController'           => 'Admin'
     * eg. 'Collection/Content/ResourceCollectionModel' => 'Resource'
     * eg. 'Collection/LogCollection'                   => 'Log'
     * eg. 'Entity/Resource'                          => 'Resource'
     * @param string $path
     * @param string $delimiter
     * @return string
     */
    public static function getHandlerPrefixFromPath($path, $delimiter = '\\')
    {
        $handler         = self::getHandlerFromPath($path, $delimiter);
        $title_word_list = Kit::separateTitleWords($handler);
        if (Kit::len($title_word_list) > 0) {
            if (TRUE === Kit::in(Kit::last($title_word_list), self::$handler_suffix_list))
                Kit::popList($title_word_list);
        }
        if (0 === Kit::len($title_word_list))
            throw new UserException("Get handler prefix of \$handler($handler) failed.");
        return Kit::join('', $title_word_list);
    }

    /**
     * Extracts handler suffix name from path.
     * eg. 'Service/AdminServiceController'           => 'Service'
     * eg. 'Collection/Content/ResourceCollectionModel' => 'Collection'
     * eg. 'Collection/LogCollection'                   => 'Collection'
     * eg. 'Entity/ResourceEntity'                    => 'Entity'
     * @param string $path
     * @param string $delimiter
     * @return string
     */
    public static function getHandlerSuffixFromPath($path, $delimiter = '\\')
    {
        $handler         = self::getHandlerFromPath($path, $delimiter);
        $title_word_list = Kit::separateTitleWords($handler);
        if (Kit::len($title_word_list) > 0) {
            if (TRUE === Kit::in($last_word = Kit::last($title_word_list), self::$handler_suffix_list))
                return $last_word;
        }
        throw new UserException("Get handler suffix of \$handler($handler) failed.");
    }

    /**
     * Extracts handler name from path.
     * eg. 'Collection/Content/ResourceCollectionModel' => 'ResourceCollectionModel'
     * @param string $path
     * @param string $delimiter
     * @return string
     */
    public static function getHandlerFromPath($path, $delimiter = '/')
    {
        return Kit::last(Kit::split($delimiter, $path));
    }

    /**
     * eg. 'Collection/Content/ResourceCollection' => 'Content/Resource'
     * eg. 'Entity/Content/ResourceEntity'       => 'Content/Resource'
     */
    public static function getModelPath($model_class_name, $delimiter = '\\')
    {
        $handler_prefix = self::getHandlerPrefixFromPath($model_class_name); // 'Resource'
        $word_list = Kit::split($delimiter, $model_class_name);
        while (Kit::len($word_list) > 0 AND 'Model' !== $word_list[0]) {
            $word_list = Kit::slice($word_list, 1);
        }
        $word_list = Kit::slice($word_list, 2); // [ 'Content', 'ResourceCollection' ]
        Kit::popList($word_list); // [ 'Content' ]
        $word_list[] = $handler_prefix; // [ 'Content', 'Resource' ]
        return Kit::join('/', $word_list); // 'Content/Resource'
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    private static function set($key, $value)
    {
        return self::$instances->set($key, $value);
    }

    /**
     * @param mixed $key
     * @return boolean
     */
    private static function has($key)
    {
        return self::$instances->has($key);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    private static function get($key)
    {
        return self::$instances->get($key);
    }
}

/**
 * Scope isolated include.
 * Prevents access to $this/self from included files.
 * @param string $file
 */
function includeFile($file)
{
    include_once $file;
}