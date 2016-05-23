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
        self::loadModel('Data/AdminData');
        self::loadModel('Feature/Core/Admin');
    }

    final protected function getCollection(&$arguments, &$post_data)
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Projection', 'SortBy', 'Skip', 'Limit']);
    }
}