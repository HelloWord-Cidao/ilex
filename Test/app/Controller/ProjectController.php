<?php

/**
 * Class ProjectController
 *
 * @method public index()
 * @method public view(mixed $id)
 */
class ProjectController extends \Ilex\Base\Controller\Base
{
    public function index()
    {
        echo('See all projects.');
    }

    /**
     * @param mixed $id
     */
    public function view($id)
    {
        echo('You\'re looking at Project-' . strval($id));
    }
}