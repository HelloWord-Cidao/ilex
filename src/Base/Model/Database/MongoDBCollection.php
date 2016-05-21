<?php

namespace Ilex\Base\Model\Database;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/**
 * Class MongoDBCollection
 * Encapsulation of basic operations of MongoDB collections.
 * @package Ilex\Base\Model\Database
 *
 * @property protected \MongoCollection $collection
 * @property protected string $collectionName
 * 
 * @method protected array|\MongoCursor find(array $criterion = [], array $projection = [], array $sort_by = NULL, int $skip = NULL, int $limit = NULL, boolean $to_count = FALSE, boolean $to_array = TRUE)
 * @method protected                    initialize()
 * @method protected boolean            insert(array $document)
 *
 * @method private mixed getId(mixed $id)
 * @method private array setRetractId(array $data)
 */
class MongoDBCollection extends BaseModel
{
    private $collection;

    public function __construct()
    {
        $this->collection = Loader::db()->selectCollection(static::$collectionName);
    }

    /**
     * @param array   $criterion
     * @param array   $projection
     * @param array   $sort_by
     * @param int     $skip
     * @param int     $limit
     * @param boolean $to_array
     * @return int|array|\MongoCursor
     */
    protected function find($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_count = FALSE, $to_array = TRUE)
    {
        $criterion = self::setRetractId($criterion);
        try {
            $cursor = $this->collection->find($criterion, $projection);
        } catch (\Exception $e) {
            // @TODO: must be the case $this->collection is null?
            // if (TRUE === $to_count) return 0;
            // if (TRUE === $to_array) return [];
            // else return FALSE;
            return Kit::extractException($e);
        }
        if (FALSE === is_null($sort_by)) $cursor = $cursor->sort($sort_by);
        if (FALSE === is_null($skip)) $cursor = $cursor->skip($skip);
        if (FALSE === is_null($limit)) $cursor = $cursor->limit($limit);
        if (TRUE === $to_count) return count(iterator_to_array($cursor)); // @todo: check efficiency
        return TRUE === $to_array ? array_values(iterator_to_array($cursor)) : $cursor;
    }

    /**
     * @param array $document
     * @return array|boolean
     */
    protected function insert($document)
    {
        $document = self::setRetractId($document);
        if (FALSE === isset($document['Meta'])) $document['Meta'] = [];
        $document['Meta']['CreateTime'] = new \MongoDate(time());
        try {
            // @todo: should really return such detail info?
            return $this->collection->insert($document, ['w' => 1]) + [T_IS_ERROR => FALSE];
        } catch(\Exception $e) {
            return Kit::extractException($e);
        }
    }

    /**
     * Normalizes _id in $data.
     * @param array $data
     * @return array
     */
    private static function setRetractId($data)
    {
        if (TRUE === isset($data['_id'])) {
            $data['_id'] = self::getId($data['_id']);
        }
        return $data;
    }

    /**
     * Normalizes $id.
     * @param mixed $id
     * @return mixed
     */
    private static function getId($id)
    {
        if (TRUE === is_string($id)) {
            try {
                return new \MongoId($id);
            } catch (\Exception $e) {
                return $id;
            }
        } else {
            return $id;
        }
    }
}
