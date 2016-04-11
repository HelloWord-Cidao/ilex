<?php

namespace Ilex\Base\Model\View;

use \Ilex\Base\Model\Base;
use \Ilex\Core\Loader;

/**
 * Class Twig
 * Encapsulation of Twig operations.
 * @package Ilex\Base\Model\View
 *
 * @property protected \Ilex\Base\Model\System\Input $Input
 *
 * @property private \Twig_Environment $twig
 * @property private array             $twigVars
 * 
 * @method public       __construct()
 * @method public mixed __get(mixed $key)
 * @method public mixed __set(mixed $key, mixed $value)
 * @method public       assign(array $vars)
 * @method public mixed get(mixed $key)
 * @method public       render(string $path)
 * @method public mixed set(mixed $key, mixed $value)
 */
class Twig extends Base
{
    protected $Input = NULL;

    private $twig     = NULL;
    private $twigVars = [];

    public function __construct()
    {
        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(Loader::APPPATH() . 'View/'), // @todo: rename the folder
            [
                'auto_reload' => TRUE,
                'cache'       => Loader::RUNTIMEPATH() . 'twig_compile/',
            ]
        );
        $this->assign([
            'title_suffix' => CFG_TITLE_SUFFIX, // Should be defined in APPPATH/Config/Const.php.
            'tpl_dir'      => 'static/',
        ]);
        $this->loadModel('System/Input');
        $this->assign([
            'GET'  => $this->Input->get(),  // array
            'POST' => $this->Input->post(), // array
        ]);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @param array $vars
     */
    public function assign($vars)
    {
        $this->twigVars = array_merge($this->twigVars, $vars);
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        return (isset($this->fakeSession[$key]) ? $this->fakeSession[$key] : NULL);
    }

    /**
     * @param string $path
     */
    public function render($path)
    {
        echo($this->twig->render($path . '.twig', $this->twigVars));
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return ($this->twigVars[$key] = $value);
    }
}