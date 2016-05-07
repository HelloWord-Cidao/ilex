<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Base\Controller\Service\BaseService;

/**
 * Class AdminService
 * @package HelloWord\Controller\Service
 */
class AdminService extends BaseService
{
    protected $Admin;

    public function __construct()
    {
        parent::__construct();
        $this->loadModel('System/Admin');
    }

    protected function countCollection(&$arguments, &$post_data)
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Limit']);
    }

    protected function getCollection(&$arguments, &$post_data)
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Projection', 'SortBy', 'Skip', 'Limit']);
    }

}