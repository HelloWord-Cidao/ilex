<?php

namespace app\Controller;

use \Ilex\Base\Controller\BaseController;

/**
 * Class ProjectController
 * @package app\Controller
 *
 * @method final public index()
 * @method final public view(mixed $id)
 */
final class ProjectController extends BaseController
{
    final public function index()
    {
        return ('See all projects.');
    }

    /**
     * @param mixed $id
     */
    final public function view($id)
    {
        return ('You\'re looking at Project-' . strval($id));
    }
}