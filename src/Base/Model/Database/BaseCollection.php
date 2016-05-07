<?php

// @TODO: add comments

namespace Ilex\Base\Model\Database;

use \Ilex\Base\Model\Database\MongoDBCollection;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Database
 */
class BaseCollection extends MongoDBCollection
{

    public function count($criterion = [], $skip = NULL, $limit = NULL)
    {
        return $this->find($criterion, [], NULL, $skip, $limit, TRUE);
    }

    public function get($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        return $this->find($criterion, $projection, $sort_by, $skip, $limit);
    }

    public function getOne($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL)
    {
        $data = $this->find($criterion, $projection, $sort_by, $skip);
        return $data[0]; // @todo: check length!
    }

    public function add($document)
    {
        return $this->insert($document);
    }

}