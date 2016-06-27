<?php

namespace app\Controller;

use \Ilex\Base\Controller\BaseController;
use \Ilex\Lib\Kit;

/**
 * Class PlayController
 * @package app\Controller
 * 
 * @method final public resolve(\Ilex\Core\Router $Router)
 * @method final public view(string $id)
 */
final class PlayController extends BaseController
{
    /**
     * @param \Ilex\Core\Router $Router
     */
    final public function resolve($Router)
    {
        Kit::log([__METHOD__, 'get / func'], FALSE);
        $Router->get('/', function () {
            return ('Come and play!');
        });
        
        Kit::log([__METHOD__, 'get /(id:num) $this, view'], FALSE);
        $Router->get('/(id:num)', $this, 'view');

        // Just a test for `group` inside a controller's `resolve`...
        Kit::log([__METHOD__, 'group /play func'], FALSE);
        $Router->group('/play', function ($Router) {
            /** @var \Ilex\Core\Router $Router */
            Kit::log([__METHOD__, 'get /(id:num) $this view'], FALSE);
            $Router->get('/(id:num)', $this, 'view');
            $Router->back();
            return $Router->result();
        });

        Kit::log([__METHOD__, 'group /no-back func'], FALSE);
        $Router->group('/no-back', function ($Router) {
            /** @var \Ilex\Core\Router $Router */
            Kit::log([__METHOD__, 'get / func'], FALSE);
            $Router->get('/', function () {
                return ('No back here...');
            });
            /*
             * 404 should be handled manually here.
             * Add `$Router->get('(all)', ...)` or `$Router->get('.*')` to response.
             * Add `$Router->back()` to fallback.
             */
            return $Router->result();
        });

        Kit::log([__METHOD__, 'get (uri:all) func'], FALSE);
        $Router->get('(uri:all)', function ($url) {
            return ('Sorry but "' . substr($url, 1) . '" is not here. 404.');
        });

        Kit::log([__METHOD__, 'return $Router->result()'], FALSE);
        return $Router->result();
    }

    /**
     * @param string $id
     */
    final public function view($id)
    {
        return ('Play No.' . $id . '?');
    }
}