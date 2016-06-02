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
        $this->loadModel('Config/AdminConfig');
        $this->loadModel('Data/AdminData');
        $this->loadModel('Feature/Core/AdminCore');
    }
}