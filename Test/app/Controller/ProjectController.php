<?php

namespace app\Controller;

use \Ilex\Base\Controller\BaseController;

/**
 * Class ProjectController
 * @package app\Controller
 *
 * @method final public static index()
 * @method final public static view(mixed $id)
 */
final class ProjectController extends BaseController
{
    final public static function index()
    {
        return ('See all projects.');
    }

    /**
     * @param mixed $id
     */
    final public static function view($id)
    {
        return ('You\'re looking at Project-' . strval($id));
    }
}