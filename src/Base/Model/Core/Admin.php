<?php

// @TODO: add comments

namespace Ilex\Base\Model\Core;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Kit;

/**
 * Class Admin
 * @package Ilex\Base\Model\Core
 */
class Admin extends BaseModel
{

    public function countCollection($arguments, $post_data, &$data, &$status)
    {
        $collection_name = $arguments['collection_name'] . 'Collection';
        unset($arguments['collection_name']);
        $criterion       = TRUE === isset($post_data['Criterion']) ? $post_data['Criterion'] : [];
        $skip            = TRUE === isset($post_data['Skip']) ? $post_data['Skip'] : NULL;
        $limit           = TRUE === isset($post_data['Limit']) ? $post_data['Limit'] : NULL;
        if (TRUE === is_null($skip) AND TRUE === isset($arguments['skip'])) $skip = $arguments['skip'];
        if (FALSE === is_null($skip)) $skip = intval($skip);
        unset($arguments['skip']);

        if (TRUE === is_null($limit) AND TRUE === isset($arguments['limit'])) $limit = $arguments['limit'];
        if (FALSE === is_null($limit)) $limit = intval($limit);
        unset($arguments['limit']);
        $criterion += Kit::recoverMongoDBQuery($arguments);

        $this->loadModel("Database/$collection_name");

        $data = $this->$collection_name->count($criterion, $skip, $limit);

        if (FALSE === is_numeric($data)) $data = Kit::generateErrorInfo('$data is not numeric.');
    }

    public function getCollection($arguments, $post_data, &$data, &$status)
    {
        $collection_name = $arguments['collection_name'] . 'Collection';
        unset($arguments['collection_name']);
        $criterion       = TRUE === isset($post_data['Criterion']) ? $post_data['Criterion'] : [];
        $projection      = TRUE === isset($post_data['Projection']) ? $post_data['Projection'] : [];
        $sort_by         = TRUE === isset($post_data['SortBy']) ? $post_data['SortBy'] : NULL;
        $skip            = TRUE === isset($post_data['Skip']) ? $post_data['Skip'] : NULL;
        $limit           = TRUE === isset($post_data['Limit']) ? $post_data['Limit'] : NULL;
        if (TRUE === is_null($skip) AND TRUE === isset($arguments['skip'])) $skip = $arguments['skip'];
        if (FALSE === is_null($skip)) $skip = intval($skip);
        unset($arguments['skip']);

        if (TRUE === is_null($limit) AND TRUE === isset($arguments['limit'])) $limit = $arguments['limit'];
        if (FALSE === is_null($limit)) $limit = intval($limit);
        unset($arguments['limit']);
        $criterion += Kit::recoverMongoDBQuery($arguments);

        $this->loadModel("Database/$collection_name");

        $data = $this->$collection_name->get($criterion, $projection, $sort_by, $skip, $limit);

        if (FALSE === is_array($data)) $data = Kit::generateErrorInfo('$data is not array.');
    }
}