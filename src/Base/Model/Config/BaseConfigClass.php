<?php

namespace Ilex\Base\Model\Config;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseConfigClass
 * Base class of config models of Ilex.
 * @package Ilex\Base\Model\Config
 */
abstract class BaseConfigClass extends BaseModel
{

    final public function validateFeaturePrivilege($handler_suffix, $method_name)
    {
        return TRUE;
    }

}