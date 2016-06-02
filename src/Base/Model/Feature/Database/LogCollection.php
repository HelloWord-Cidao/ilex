<?php

namespace Ilex\Base\Model\Feature\Database;

use \Ilex\Base\Model\Feature\Database\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Feature\Database
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

    final protected function addRequestLog($log)
    {
        return $this->call('addLog', [ $log, 'Request' ]);
    }

    final protected function addLog($log, $type = NULL)
    {
        return $this->call('add', [ $log, $type ]);
    }
}