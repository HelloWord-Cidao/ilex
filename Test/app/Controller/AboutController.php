<?php

namespace app\Controller;
use \Ilex\Base\Controller\BaseController;

/**
 * Class AboutController
 * @package app\Controller
 *
 * @method final public index()
 * @method final public join(string $group = 'tech')
 * @method final public postJoin(string $group = 'tech')
 */
final class AboutController extends BaseController
{
    final public function index()
    {
        return ('about');
    }

    /**
     * @param string $group
     */
    final public function join($group = 'tech')
    {
        return ('Join ' . $group . '!');
    }

    /**
     * @param string $group
     */
    final public function postJoin($group = 'tech')
    {
        // This will assign the instance of the loaded model to self::Input.
        self::loadModel('System/Input');
        return ('Welcome to ' . $group . ', ' . self::$Input->post('name', 'Jack') . '!');
    }
}