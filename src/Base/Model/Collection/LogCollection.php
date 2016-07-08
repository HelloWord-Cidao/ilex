<?php

namespace Ilex\Base\Model\Collection;

use \Ilex\Base\Model\Collection\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Collection
 */
class LogCollection extends BaseCollection
{

    final public function addRequestLog($content)
    {
        $type = 'Request';
        return $this->call('addLog', $content, $type);
    }

    final public function addLog($content, $type = NULL)
    {
        if (FALSE === is_null($type))
            $meta = [ 'Type' => $type ];
        else $meta = [];
        return $this->call('addOne', $content, $meta);
    }
}