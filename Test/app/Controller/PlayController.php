<?php

/**
 * Class PlayController
 * 
 * @method public resolve(\Ilex\Core\Route $Route)
 * @method public view(string $id)
 */
class PlayController extends \Ilex\Base\Controller\Base
{
    /**
     * @param \Ilex\Core\Route $Route
     */
    public function resolve($Route)
    {
        $Route->get('/', function () {
            return ('Come and play!');
        });
        $Route->get('/(num)', $this, 'view');

        // Just a test for `group` inside a controller's `resolve`...
        $Route->group('/play', function ($Route) {
            /** @var \Ilex\Core\Route $Route */
            $Route->get('/(num)', $this, 'view');
            $Route->back();
        });

        $Route->group('/no-back', function ($Route) {
            /** @var \Ilex\Core\Route $Route */
            $Route->get('/', function () {
                return ('No back here...');
            });
            /*
             * 404 should be handled manually here.
             * Add `$Route->get('(all)', ...)` or `$Route->get('.*')` to response.
             * Add `$Route->back()` to fallback.
             */
        });

        $Route->get('(all)', function ($url) {
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