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

    final public function validateFeaturePrivilege($method_name, $handler_suffix)
    {
        return TRUE;
    }

}