<?php

namespace Ilex\Base\Model\Feature\Database;

use \Ilex\Base\Model\Feature\Database\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Feature\Database
 */
class LogCollection extends BaseCollection
{
    const METHODS_VISIBILITY = [
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
        return $this->__call('addLog', [ $log, 'Request' ]);
    }

    final protected function addLog($log, $type = NULL)
    {
        return $this->__call('add', [ $log, $type ]);
    }
}