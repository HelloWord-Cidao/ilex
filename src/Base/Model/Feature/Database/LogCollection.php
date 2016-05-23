<?php

// @TODO: add comments

namespace Ilex\Base\Model\Feature\Database;

use \Ilex\Base\Model\Feature\Database\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Feature\Database
 *
 * @method final protected array|boolean addLog(array $log)
 */
class LogCollection extends BaseCollection
{
    // $collectionName should be static, because any instance of this class can not change this value at runtime.
    protected static $collectionName = 'Log';

    /**
     * @param array $log
     * @return array|boolean
     */
    final protected function addLog($log)
    {
        if (FALSE === isset($log['Meta']))
            $log['Meta'] = [];
        if (FALSE === isset($log['Meta']['Type']))
            $log['Meta']['Type'] = 'Log';
        return $this->add($log);
    }
}