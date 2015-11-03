<?php

namespace Ilex\Base\Model\Database;

use \Ilex\Core\Loader;

/**
 * Class Base
 * Encapsulation of database operations.
 * @package Ilex\Base\Model\Database
 *
 * @property public \MongoCollection $collection
 *
 * @property protected string $collectionName
 * 
 * @method public                    __construct()
 * @method public array|\MongoCursor find(array $criterion = [], array $projection = [], boolean $toArray = TRUE)
 *
 * @method protected mixed getId(mixed $id)
 * @method protected array setRetractId(array $data)
 */
class Base extends \Ilex\Base\Model\Base
{
    public $collection; // @todo: Do NOT expose this! Change to protected!

    protected $collectionName;

    public function __construct()
    {
        $this->collection = Loader::db()->selectCollection($this->collectionName);
    }

    /**
     * @param array   $criterion
     * @param array   $projection
     * @param boolean $toArray
     * @return array|\MongoCursor
     */
    public function find($criterion = [], $projection = [], $toArray = TRUE)
    {
        $criterion = $this->setRetractId($criterion);
        $cursor = $this->collection->find($criterion, $projection);
        return $toArray ? array_values(iterator_to_array($cursor)) : $cursor;
    }

    /**
     * Normalizes $id.
     * @param mixed $id
     * @return mixed
     */
    protected function getId($id)
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
    protected function setRetractId($data)
    {
        if (isset($data['_id'])) {
            $data['_id'] = $this->getId($data['_id']);
        }
        return $data;
    }
}
