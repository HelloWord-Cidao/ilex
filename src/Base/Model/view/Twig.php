<?php

namespace Ilex\Base\Model\view;

use \Ilex\Base\Model\Base;
use \Ilex\Core\Loader;

/**
 * Class Twig
 * Encapsulation of Twig operations.
 * @package Ilex\Base\Model\view
 *
 * @property array                        $twigVars
 * @property \Twig_Environment            $twig
 * @property \Ilex\Base\Model\sys\Input   $Input
 * @property \Ilex\Base\Model\sys\Session $Session
 * 
 * @method public                            __construct()
 * @method public \Ilex\Base\Model\view\Twig render(string $path)
 * @method public \Ilex\Base\Model\view\Twig assign(array $vars)
 * @method public mixed                      let(mixed $k, mixed $v)
 */
class Twig extends Base
{
    protected $twigVars = [];
    protected $twig     = NULL;
    protected $Input    = NULL;
    protected $Session  = NULL;

    public function __construct()
    {
        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(Loader::APPPATH() . 'View/'), // @todo: rename the folder
            [
                'cache' => Loader::RUNTIMEPATH() . 'twig_compile/',
                'auto_reload' => TRUE
            ]
        );
        $this->assign([
            'title_suffix' => CFG_TITLE_SUFFIX, // Should be defined in APPPATH/Config/Const.php.
            'tpl_dir'      => 'static/'
        ));
        $this->loadModel('sys/Input');
        $this->assign([
            'POST' => $this->Input->post(), // array
            'GET'  => $this->Input->get()   // array
        ]);
    }

    /**
     * @param string $path
     * @return \Ilex\Base\Model\view\Twig
     */
    public function render($path)
    {
        echo($this->twig->render($path . '.twig', $this->twigVars));
        return $this;
    }

    /**
     * @param array $vars
     * @return \Ilex\Base\Model\view\Twig
     */
    public function assign($vars)
    {
        $this->twigVars = array_merge($this->twigVars, $vars);
        return $this;
    }

    /**
     * @param mixed $k
     * @param mixed $v
     * @return mixed
     */
    public function let($k, $v)
    {
        return ($this->twigVars[$k] = $v);
    }
}