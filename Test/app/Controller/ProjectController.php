<?php

namespace app\Controller;

/**
 * Class ProjectController
 * @package Ilex\Test\app\Controller
 *
 * @method public index()
 * @method public view(mixed $id)
 */
class ProjectController extends \Ilex\Base\Controller\Base
{
    public function index()
    {
        return ('See all projects.');
    }

    /**
     * @param mixed $id
     */
    public function view($id)
    {
        return ('You\'re looking at Project-' . strval($id));
    }
}