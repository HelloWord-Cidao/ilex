<?php

namespace app\Controller;

/**
 * Class PlayController
 * @package app\Controller
 * 
 * @method public resolve(\Ilex\Core\Router $Router)
 * @method public view(string $id)
 */
class PlayController extends \Ilex\Base\Controller\Base
{
    /**
     * @param \Ilex\Core\Router $Router
     */
    public function resolve($Router)
    {
        $Router->get('/', function () {
            return ('Come and play!');
        });
        $Router->get('/(num)', $this, 'view');

        // Just a test for `group` inside a controller's `resolve`...
        $Router->group('/play', function ($Router) {
            /** @var \Ilex\Core\Router $Router */
            $Router->get('/(num)', $this, 'view');
            $Router->back();
        });

        $Router->group('/no-back', function ($Router) {
            /** @var \Ilex\Core\Router $Router */
            $Router->get('/', function () {
                return ('No back here...');
            });
            /*
             * 404 should be handled manually here.
             * Add `$Router->get('(all)', ...)` or `$Router->get('.*')` to response.
             * Add `$Router->back()` to fallback.
             */
        });

        $Router->get('(all)', function ($url) {
            return ('Sorry but "' . substr($url, 1) . '" is not here. 404.');
        });
    }

    /**
     * @param string $id
     */
    public function view($id)
    {
        return ('Play No.' . $id . '?');
    }
}