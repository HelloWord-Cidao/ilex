<?php

namespace app\Controller;

/**
 * Class AboutController
 * @package app\Controller
 *
 * @property protected \Ilex\Base\Model\sys\Input $Input
 * 
 * @method public index()
 * @method public join(string $group = 'tech')
 * @method public postJoin(string $group = 'tech')
 */
class AboutController extends \Ilex\Base\Controller\Base
{
    protected $Input = NULL;

    public function index()
    {
        return ('about');
    }

    /**
     * @param string $group
     */
    public function join($group = 'tech')
    {
        return ('Join ' . $group . '!');
    }

    /**
     * @param string $group
     */
    public function postJoin($group = 'tech')
    {
        // This will assign the instance of the loaded model to $this->Input.
        $this->loadModel('sys/Input');
        return ('Welcome to ' . $group . ', ' . $this->Input->post('name', 'Jack') . '!');
    }
}