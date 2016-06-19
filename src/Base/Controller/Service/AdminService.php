<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Base\Controller\Service\BaseService;

/**
 * Class AdminService
 * @package Ilex\Base\Controller\Service
 */
final class AdminService extends BaseService
{
    public function __construct()
    {
        $path = 'Admin';
        $this->loadConfig($path);
        $this->loadData($path);
        $this->loadCore($path);
    }
}