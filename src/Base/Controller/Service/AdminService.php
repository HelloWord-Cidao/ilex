<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Base\Controller\Service\BaseService;

/**
 * Class AdminService
 * @package HelloWord\Controller\Service
 */
class AdminService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->loadModel('Data/AdminData');
        $this->loadModel('Core/Admin');
    }

    protected function getCollection(&$arguments, &$post_data)
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Projection', 'SortBy', 'Skip', 'Limit']);
    }
}