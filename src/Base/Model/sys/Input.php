<?php

namespace Ilex\Base\Model\sys;

use \Ilex\Base\Model\Base;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * Class Input
 * Encapsulation of system input, such as $_GET, $_POST.
 * @package Ilex\Base\Model\sys
 * 
 * @property private \Ilex\Lib\Container $get
 * @property private \Ilex\Lib\Container $post
 * 
 * @method public                            __construct()
 * @method public string                     __toString()
 * @method public \Ilex\Base\Model\sys\Input merge(string $name, array $data = [])
 * @method public \Ilex\Base\Model\sys\Input clear(string $name = '')
 * @method public mixed                      get(string $key = NULL, mixed $default = NULL)
 * @method public mixed                      post(string $key = NULL, mixed $default = NULL)
 */
class Input extends Base
{
    // @todo: public or private?
    private $get;
    private $post;

    /**
     * Encapsulates global variables.
     */
    public function __construct()
    {
        $this->get = new Container($_GET);
        $this->post = new Container($_POST);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString([
            'get'  => $this->get,
            'post' => $this->post
        ]);
    }

    /**
     * @param string $name
     * @param array  $data
     * @return \Ilex\Base\Model\sys\Input
     */
    public function merge($name, $data = [])
    {
        $this->$name->merge($data);
        return $this;
    }

    /**
     * If $name is NOT assigned, $get and $post both will be cleared.
     * @param string $name
     * @return \Ilex\Base\Model\sys\Input
     */
    public function clear($name = '')
    {
        if ($name) {
            $this->$name->assign();
        } else {
            $this->get->assign();
            $this->post->assign();
        }
        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key = NULL, $default = NULL) {
        return $this->get->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function post($key = NULL, $default = NULL) {
        return $this->post->get($key, $default);
    }
}
