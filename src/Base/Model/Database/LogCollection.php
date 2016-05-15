<?php

// @TODO: add comments

namespace Ilex\Base\Model\Database;

use \Ilex\Base\Model\Database\BaseCollection;

/**
 * Class LogCollection
 * @package Ilex\Base\Model\Database
 *
 * @method public array|boolean addLog(array $log)
 */
class LogCollection extends BaseCollection
{
    protected $collectionName = 'Log';

    public function __construct()
    {
        $this->initialize($this->collectionName);
    }

    /**
     * @param array $log
     * @return array|boolean
     */
    public function addLog($log)
    {
        if (FALSE === isset($log['Meta']))
            $log['Meta'] = [];
        if (FALSE === isset($log['Meta']['Type']))
            $log['Meta']['Type'] = 'Log';
        return $this->add($log);
    }
}