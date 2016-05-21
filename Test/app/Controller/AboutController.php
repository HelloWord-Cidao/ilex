<?php

namespace app\Controller;
use \Ilex\Base\Controller\BaseController;

/**
 * Class AboutController
 * @package app\Controller
 *
 * @method final public static index()
 * @method final public static join(string $group = 'tech')
 * @method final public static postJoin(string $group = 'tech')
 */
final class AboutController extends BaseController
{
    final public static function index()
    {
        return ('about');
    }

    /**
     * @param string $group
     */
    final public static function join($group = 'tech')
    {
        return ('Join ' . $group . '!');
    }

    /**
     * @param string $group
     */
    final public static function postJoin($group = 'tech')
    {
        // This will assign the instance of the loaded model to $this->Input.
        self::loadModel('System/Input');
        return ('Welcome to ' . $group . ', ' . self::$Input->post('name', 'Jack') . '!');
    }
}