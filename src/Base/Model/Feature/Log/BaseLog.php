<?php

namespace Ilex\Base\Model\Feature\Log;

use \Ilex\Base\Model\Feature\BaseFeature;
/**
 * Class BaseLog
 * @package Ilex\Base\Model\Feature\Log
 */
abstract class BaseLog extends BaseFeature
{
    const METHODS_VISIBILITY = [
        self::V_PROTECTED => [
        ],
    ];

}