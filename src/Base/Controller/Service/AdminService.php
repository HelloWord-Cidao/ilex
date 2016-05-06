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

    public function countCollection()
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Limit']);

        $computation_data = $this->Admin->countCollection($arguments, $post_data);
        $this->validateComputationData($computation_data);
        
        $this->Log->logRequest(__METHOD__, NULL, $arguments, $post_data);

        $this->responseWithSuccess($computation_data, NULL);
    }

    public function getCollection()
    {
        $this->validateExistArguments(['collection_name']);
        $arguments = $this->fetchAllArguments();
        $post_data = $this->tryFetchPostData(['Criterion', 'Projection', 'SortBy', 'Skip', 'Limit']);

        $computation_data = $this->Admin->getCollection($arguments, $post_data);
        $this->validateComputationData($computation_data);
        
        $this->Log->logRequest(__METHOD__, NULL, $arguments, $post_data);

        $this->responseWithSuccess($computation_data, NULL);
    }

}