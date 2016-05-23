<?php

// @TODO: add comments

namespace Ilex\Base\Model\Feature\Database;

use \Ilex\Base\Model\Feature\Database\MongoDBCollection;

/**
 * Class BaseCollection
 * Base class of collection models of Ilex.
 * @package Ilex\Base\Model\Feature\Database
 */
abstract class BaseCollection extends MongoDBCollection
{

    final protected function count($criterion = [], $skip = NULL, $limit = NULL)
    {
        return $this->find($criterion, [], NULL, $skip, $limit, TRUE);
    }

    final protected function get($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL, $limit = NULL)
    {
        return $this->find($criterion, $projection, $sort_by, $skip, $limit);
    }

    final protected function getOne($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL)
    {
        $data = $this->find($criterion, $projection, $sort_by, $skip);
        return $data[0]; // @todo: check length!
    }

    final protected function add($document)
    {
        return $this->insert($document);
    }

}