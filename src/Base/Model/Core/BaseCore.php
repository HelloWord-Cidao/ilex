<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseCore
 * Base class of core models of Ilex.
 * @package Ilex\Base\Model\Core
 */
abstract class BaseCore extends BaseModel
{
    const S_OK = 'ok';
    protected $ok = [ self::S_OK => TRUE ];

    public function __construct($user = NULL)
    {
        $this->user = $user;
    }
}