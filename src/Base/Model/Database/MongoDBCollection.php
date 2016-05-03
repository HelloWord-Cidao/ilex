<?php

namespace Ilex\Base\Model\Database;

use \Ilex\Base\Model\Base;
use \Ilex\Core\Loader;

/**
 * Class MongoDBCollection
 * Encapsulation of basic operations of MongoDB collections.
 * @package Ilex\Base\Model\Database
 *
 * @property protected \MongoCollection $collection
 * @property protected string $collectionName
 * 
 * @method protected                    initialize()
 * @method protected array|\MongoCursor find(array $criterion = [], array $projection = [], boolean $toArray = TRUE)
 * @method protected boolean insert(array $document)
 *
 * @method private mixed getId(mixed $id)
 * @method private array setRetractId(array $data)
 */
class MongoDBCollection extends Base
{
    protected $collectionName;
    
    private $collection;

    protected function initialize($collectionName)
    {
        $this->collection = Loader::db()->selectCollection($collectionName);
    }

    /**
     * @param array   $criterion
     * @param array   $projection
     * @param boolean $toArray
     * @return array|\MongoCursor
     */
    protected function find($criterion = [], $projection = [], $toArray = TRUE)
    {
        $criterion = $this->setRetractId($criterion);
        $cursor = $this->collection->find($criterion, $projection);
        return $toArray ? array_values(iterator_to_array($cursor)) : $cursor;
    }

    /**
     * @param array $document
     * @return boolean
     */
    protected function insert($document)
    {
        if (!isset($document['Meta'])) $document['Meta'] = [];
        $document['Meta']['CreateTime'] = new \MongoDate(time());
        try {
            $this->collection->insert($document, ['w' => 1]);
            return TRUE;
        } catch(\Exception $e) {
            return [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ];
        }
    }

    /**
     * Normalizes $id.
     * @param mixed $id
     * @return mixed
     */
    private function getId($id)
    {
        if (is_string($id)) {
            try {
                return new \MongoId($id);
            } catch (\Exception $e) {
                return $id;
            }
        } else {
            return $id;
        }
    }

    /**
     * Normalizes _id in $data.
     * @param array $data
     * @return array
     */
    private function setRetractId($data)
    {
        if (isset($data['_id'])) {
            $data['_id'] = $this->getId($data['_id']);
        }
        return $data;
    }
}
