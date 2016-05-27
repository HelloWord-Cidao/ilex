<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Base\Controller\Service\BaseService;

/**
 * Class AdminService
 * @package Ilex\Base\Controller\Service
 */
class AdminService extends BaseService
{
    final public function __construct()
    {
        parent::__construct();
        $this->loadModel('Data/AdminData');
        $this->loadModel('Feature/Core/Admin');
    }
}