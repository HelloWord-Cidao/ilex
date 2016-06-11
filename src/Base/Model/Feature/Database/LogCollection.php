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

    final public function __construct()
    {
        parent::__construct();
        $this->loadModel('Config/LogConfig');
        $this->loadModel('Data/LogData');
    }

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
        return $this->call('add', $content, $meta);
    }
}