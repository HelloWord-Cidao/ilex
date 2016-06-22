<?php

namespace Ilex\Base\Model\Collection;

use \Ilex\Base\Model\Collection\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Collection
 */
class LogCollection extends BaseCollection
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'addRequestLog',
        ],
        self::V_PROTECTED => [
            'addLog',
        ]
    ];

    const COLLECTION_NAME = 'Log';
    const ENTITY_PATH     = 'Log';

    final protected function addRequestLog($content)
    {
        $type = 'Request';
        return $this->call('addLog', $content, $type);
    }

    final protected function addLog($content, $type = NULL)
    {
        if (FALSE === is_null($type))
            $meta = [ 'Type' => $type ];
        else $meta = [];
        return $this->call('addOne', $content, $meta);
    }
}