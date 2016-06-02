<?php

namespace Ilex\Base\Model\Config;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseConfig
 * Base class of config models of Ilex.
 * @package Ilex\Base\Model\Config
 */
abstract class BaseConfig extends BaseModel
{

    final public function validateFeaturePrivilege($method_name)
    {
        return TRUE;
    }

}