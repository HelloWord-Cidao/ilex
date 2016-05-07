<?php

// @TODO: add comments

namespace Ilex\Base\Model\System;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Kit;

/**
 * Class Admin
 * @package Ilex\Base\Model\System
 */
class Admin extends BaseModel
{

    public function countCollection($arguments, $post_data, &$data, &$status)
    {
        $collection_name = $arguments['collection_name'] . 'Collection';
        unset($arguments['collection_name']);
        $criterion       = isset($post_data['Criterion']) ? $post_data['Criterion'] : [];
        $skip            = isset($post_data['Skip']) ? $post_data['Skip'] : NULL;
        $limit           = isset($post_data['Limit']) ? $post_data['Limit'] : NULL;
        if (is_null($skip) && isset($arguments['skip'])) $skip = $arguments['skip'];
        if (!is_null($skip)) $skip = intval($skip);
        unset($arguments['skip']);

        if (is_null($limit) && isset($arguments['limit'])) $limit = $arguments['limit'];
        if (!is_null($limit)) $limit = intval($limit);
        unset($arguments['limit']);
        $criterion += Kit::recoverMongoDBQuery($arguments);

        $this->loadModel("Database/$collection_name");

        $data = $this->$collection_name->count($criterion, $skip, $limit);

        if (!is_numeric($data)) $data = $this->generateErrorInfo('$data is not numeric.');
    }

    public function getCollection($arguments, $post_data, &$data, &$status)
    {
        $collection_name = $arguments['collection_name'] . 'Collection';
        unset($arguments['collection_name']);
        $criterion       = isset($post_data['Criterion']) ? $post_data['Criterion'] : [];
        $projection      = isset($post_data['Projection']) ? $post_data['Projection'] : [];
        $sort_by         = isset($post_data['SortBy']) ? $post_data['SortBy'] : NULL;
        $skip            = isset($post_data['Skip']) ? $post_data['Skip'] : NULL;
        $limit           = isset($post_data['Limit']) ? $post_data['Limit'] : NULL;
        if (is_null($skip) && isset($arguments['skip'])) $skip = $arguments['skip'];
        if (!is_null($skip)) $skip = intval($skip);
        unset($arguments['skip']);

        if (is_null($limit) && isset($arguments['limit'])) $limit = $arguments['limit'];
        if (!is_null($limit)) $limit = intval($limit);
        unset($arguments['limit']);
        $criterion += Kit::recoverMongoDBQuery($arguments);

        $this->loadModel("Database/$collection_name");

        $data = $this->$collection_name->get($criterion, $projection, $sort_by, $skip, $limit);

        if (!is_array($data)) $data = $this->generateErrorInfo('$data is not array.');
    }
}