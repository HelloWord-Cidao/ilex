<?php

namespace Ilex\Base\Model\Feature\Database;

use \Exception;
use \MongoId;
use \MongoDate;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Feature\BaseFeature;
use \Ilex\Base\Model\Feature\Database\MongoDBCursor;

/**
 * Class MongoDBCollection
 * Encapsulation of basic operations of MongoCollection class.
 * @package Ilex\Base\Model\Feature\Database
 *
 * @property private \MongoCollection $collection
 *
 * @method final public                   __construct()
 * @method final protected        boolean checkExistence(array $criterion)
 * @method final protected        boolean checkExistsOnlyOne(array $criterion)
 * @method final protected static string  convertMongoIdToString(MongoId $id)
 * @method final protected static MongoId convertStringToMongoId(string $id)
 * @method final protected        int     count(array $criterion = [], int $skip = NULL, int $limit = NULL)
 * @method final protected static array   sanitizeCriterion(array $criterion)
 * 
 * @method final private int                 mongoCount(array $criterion = [], int $skip = NULL
 *                                                     , int $limit = NULL)
 * @method final private array|MongoDBCursor mongoFind(array $criterion = [], array $projection = []
 *                                                     , array $sort_by = NULL, int $skip = NULL
 *                                                     , int $limit = NULL, boolean $to_count = FALSE
 *                                                     , boolean $to_array = TRUE)
 * @method final private array|NULL          mongoFindOne(array $criterion = [], array $projection = [])
 * @method final private boolean             mongoInsert(array $document)
 * @method final private                     mongoUpdate()
 */
abstract class MongoDBCollection extends BaseFeature
{
    // https://docs.mongodb.com/manual/core/document-validation/
    // http://blog.mongodb.org/post/87200945828/6-rules-of-thumb-for-mongodb-schema-design-part-1
    // https://www.mongodb.com/presentations/socialite-open-source-status-feed-part-2-managing-social-graph
    // https://www.mongodb.com/webinars
    // https://www.mongodb.com/white-papers
    // https://university.mongodb.com/courses/catalog?jmp=footer&_ga=1.161627452.1639095796.1462556963
    // http://snmaynard.com/2012/10/17/things-i-wish-i-knew-about-mongodb-a-year-ago/
    // 
    // add comments
    // implement update save insert

    private $collection;

    final public function __construct()
    {
        $this->collection = Loader::db()->selectCollection(static::collectionName);
    }

    /**
     * 
     * @param 
     * @return 
     */
    final protected function add($document)
    {
        // $document = self::sanitizeCriterion($document);
        if (FALSE === isset($document['Meta'])) $document['Meta'] = [];
        $document['Meta']['CreationTime'] = new MongoDate(time());
        return $this->mongoInsert($document);
    }

    /**
     * Checks whether there is at least one document matching the query.
     * Throws MongoResultException if the server could not execute the command due to an error.
     * Throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     * @param array $criterion Associative array or object with fields to match.
     * @return boolean Returns whether there is at least one document matching the query.
     */
    final protected function checkExistence($criterion)
    {
        $result = $this->count($criterion, NULL, 1);
        if (TRUE === Kit::checkIsError($result)) return $result;
        else return ($result > 0);
    }

    /**
     * Checks whether there is one and only one document matching the query.
     * Throws MongoResultException if the server could not execute the command due to an error.
     * Throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     * @param array $criterion Associative array or object with fields to match.
     * @return boolean Returns whether there is one and only one document matching the query.
     */
    final protected function checkExistsOnlyOne($criterion)
    {
        $result = $this->count($criterion, NULL, 2);
        if (TRUE === Kit::checkIsError($result)) return $result;
        else return (1 === $result);
    }

    /**
     * Counts the number of documents in this collection.
     * Throws MongoResultException if the server could not execute the command due to an error.
     * Throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     * @param array $criterion Associative array or object with fields to match.
     * @param int   $skip The number of matching documents to skip before returning results.
     * @param int   $limit The maximum number of matching documents to return.
     * @return int Returns the number of documents matching the query.
     */
    final protected function count($criterion = [], $skip = NULL, $limit = NULL)
    {
        $criterion = self::sanitizeCriterion($criterion);
        return $this->mongoCount($criterion, $skip, $limit);
    }

    final protected function get($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_array = FALSE)
    {
        $criterion = self::sanitizeCriterion($criterion);
        return $this->mongoFind($criterion, $projection, $sort_by, $skip, $limit, $to_array);
    }

    final protected function getOne($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL)
    {
        $criterion = self::sanitizeCriterion($criterion);
        if (TRUE === is_null($sort_by) AND TRUE === is_null($skip)) {
            $result = $this->mongoFindOne($criterion, $projection);
            if (TRUE === Kit::checkIsError($result)) return $result;
            if (TRUE === is_null($result)) $result = [];
            else $result = [ $result ];
        } else {
            $result = $this->mongoFind($criterion, $projection, $sort_by, $skip, 1, TRUE);
            if (TRUE === Kit::checkIsError($result)) return $result;
        }
        if (0 === count($result)) {
            $msg = 'Collection Operation(getOne) failed: no document found.';
            return Kit::generateError($msg, [
                'criterion'  => $criterion,
                'projection' => $projection,
                'sort_by'    => $sort_by,
                'skip'       => $skip,
            ]);
        } else return $result[0];
    }

    final protected function getTheOnlyOne($criterion = [], $projection = [])
    {
        $criterion = self::sanitizeCriterion($criterion);
        if (FALSE === $this->checkExistsOnlyOne($criterion)) {
            $msg = 'Collection Operation(getTheOnlyOne) failed: no or more than one documents found.';
            return Kit::generateError($msg, [
                'criterion'  => $criterion,
                'projection' => $projection,
            ]);
        }
        return $this->getOne($criterion, $projection);
    }

    final protected function update()
    {
        $criterion = self::sanitizeCriterion($criterion);
        return $this->mongoUpdate();
    }

    /**
     * Sanitize _id in $criterion.
     * This method will not throw any exception.
     * @param array $criterion
     * @return array
     */
    final protected static function sanitizeCriterion($criterion)
    {
        if (TRUE === isset($criterion['_id'])) {
            $criterion['_id'] = self::convertStringToMongoId($criterion['_id']);
        }
        return $criterion;
    }

    /**
     * Converts a string to a MongoId.
     * @param string $id
     * @return MongoId
     */
    final protected static function convertStringToMongoId($id)
    {
        if (TRUE === is_string($id)) {
            try {
                return new MongoId($id);
            } catch (Exception $e) {
                $msg = 'Collection Operation(convertStringToMongoId) failed: can not be parsed as a MongoId.';
                return Kit::generateError($msg, [
                    'id' => $id,
                    'exception' => Kit::extractException($e),
                ]);
            }
        } else {
            $msg = 'Collection Operation(convertStringToMongoId) failed: $id is not a string.';
            return Kit::generateError($msg, [
                'id' => $id,
            ]);
        }
    }

    /**
     * Converts a MongoId to a string.
     * @param MongoId $id
     * @return string
     */
    final protected static function convertMongoIdToString($id)
    {
        if (FALSE === ($id instanceof MongoId)) {
            $msg = 'Collection Operation(convertMongoIdToString) failed: $id is not a MongoId.';
            return Kit::generateError($msg, [
                'id' => $id,
            ]);
        }
        return strval($id);
    }

    // ================================================================================
    // Below are private methods that interact directly with MongoCollection methods.
    // ================================================================================

    /**
     * @param array $document
     * @return array|boolean
     */
    final private function mongoInsert($document)
    {
        try {
            return $this->collection->insert($document, ['w' => 1]);
        } catch(Exception $e) {
            return Kit::generateError('MongoDB Collection operation(insert) failed.', [
                'document'  => $document,
                'exception' => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Counts the number of documents in this collection.
     * Throws MongoResultException if the server could not execute the command due to an error.
     * Throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     * @param array $criterion Associative array or object with fields to match.
     * @param int   $skip The number of matching documents to skip before returning results.
     * @param int   $limit The maximum number of matching documents to return.
     * @return int Returns the number of documents matching the query.
     */
    final private function mongoCount($criterion = [], $skip = NULL, $limit = NULL)
    {
        try {
            $options   = [];
            if (FALSE === is_null($skip))  $options['skip']  = $skip;
            if (FALSE === is_null($limit)) $options['limit'] = $limit;
            // @todo: add $hint, Index to use for the query.
            return $this->collection->count($criterion, $options);
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Collection operation(count) failed.', [
                'criterion' => $criterion,
                'skip'      => $skip,
                'limit'     => $limit,
                'exception' => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Queries this collection, returning an array or a MongoDBCursor for the result set.
     * @param array   $criterion  The fields for which to search.
     * @param array   $projection Fields of the results to return. The _id field is always returned.
     * @param array   $sort_by    An array of fields by which to sort.
     *                            Each element in the array has as key the field name,
     *                            and as value either 1 for ascending sort, or -1 for descending sort.
     *                            Each result is first sorted on the first field in the array,
     *                            then (if it exists) on the second field in the array, etc.
     *                            This means that the order of the fields in the fields array is important.
     * @param int     $skip       The number of results to skip.
     * @param int     $limit      The number of results to return.
     * @param boolean $to_array   Whether return an array instead of a MongoDBCursor.
     * @return array|MongoDBCursor Returns an array or a cursor for the search results.
     */
    final private function mongoFind($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_array = FALSE)
    {
        try {
            $cursor = $this->collection->find($criterion, $projection);
            // @todo: following may cause what exception?
            if (FALSE === is_null($sort_by)) $cursor = $cursor->sort($sort_by);
            if (FALSE === is_null($skip))    $cursor = $cursor->skip($skip);
            if (FALSE === is_null($limit))   $cursor = $cursor->limit($limit);
            if (TRUE === $to_array) return iterator_to_array($cursor, FALSE);
            else new MongoDBCursor($cursor);
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Collection operation(find) failed.', [
                'criterion'  => $criterion,
                'projection' => $projection,
                'sortBy'     => $sort_by,
                'skip'       => $skip,
                'limit'      => $limit,
                'toArray'    => $to_array,
                'exception'  => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Queries this collection, returning a single element.
     * Throws MongoConnectionException if it cannot reach the database.
     * @param array $criterion  The fields for which to search.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array|NULL Returns record matching the search or NULL.
     */
    final private function mongoFindOne($criterion = [], $projection = [])
    {
        try {
            return $this->collection->findOne($criterion, $projection);
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Collection operation(findOne) failed.', [
                'criterion'  => $criterion,
                'projection' => $projection,
                'exception'  => Kit::extractException($e),
            ]);
        }
    }

    /**
     * @param array $criterion
     * @param int   $skip
     * @param int   $limit
     * @return 
     */
    final private function mongoUpdate($criterion = [], $skip = NULL, $limit = NULL)
    {
        try {
            // $cursor = $this->collection->find($criterion, $projection);
            // set Meta.ModificationTime
        } catch (Exception $e) {
            return Kit::extractException($e);
        }
        return FALSE;
        // if (FALSE === is_null($skip))    $cursor = $cursor->skip($skip);
        // if (FALSE === is_null($limit))   $cursor = $cursor->limit($limit);
        // return TRUE === $to_array ? array_values(iterator_to_array($cursor)) : $cursor;
    }
}

    // aggregate
    // aggregateCursor
    // batchInsert
    // createDBRef
    // createIndex
    // deleteIndex
    // deleteIndexes
    // distinct
    // drop
    // ensureIndex
    // findAndModify
    // _​_​get
    // getDBRef
    // getIndexInfo
    // getName
    // getReadPreference
    // getSlaveOkay
    // getWriteConcern
    // group
    // parallelCollectionScan
    // remove
    // save
    // setReadPreference
    // setSlaveOkay
    // setWriteConcern
    // toIndexString
    // _​_​toString
    // validate
