<?php

namespace Ilex\Base\Model\Feature\Log;

use \Ilex\Base\Model\Feature\BaseFeature;
/**
 * Class BaseLog
 * @package Ilex\Base\Model\Feature\Log
 *
 * @method final public                      __construct()
 *
 * @method final protected static array|boolean addLog(array $log)
 */
abstract class BaseLog extends BaseFeature
{

    final public function __construct()
    {
        $this->loadModel('Feature/Database/LogCollection');
    }

    final protected function addLog($log)
    {
        return $this->LogCollection->addLog($log);
    }

}