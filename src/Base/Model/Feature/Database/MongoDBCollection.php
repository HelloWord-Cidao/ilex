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
 * @method final public                               __construct()
 * @method final protected        MongoId             add(array $document)
 * @method final protected        boolean             checkExistence(array $criterion)
 * @method final protected        boolean             checkExistsOnlyOne(array $criterion)
 * @method final protected static string              convertMongoIdToString(MongoId $id)
 * @method final protected static MongoId             convertStringToMongoId(string $id)
 * @method final protected        int                 count(array $criterion = []
 *                                                        , int $skip = NULL
 *                                                        , int $limit = NULL)
 * @method final protected        array|MongoDBCursor get(array $criterion = []
 *                                                        , array $projection = []
 *                                                        , array $sort_by = NULL
 *                                                        , int $skip = NULL
 *                                                        , int $limit = NULL
 *                                                        , boolean $to_array = TRUE)
 * @method final protected        array               getOne(array $criterion = []
 *                                                        , array $projection = []
 *                                                        , array $sort_by = NULL
 *                                                        , int $skip = NULL)
 * @method final protected        array               getTheOnlyOne(array $criterion = []
 *                                                        , array $projection = [])
 * @method final protected        array               update(array $criterion
 *                                                        , array $update
 *                                                        , boolean $multiple = FALSE)
 * @method final protected static array               sanitizeCriterion(array $criterion)
 * 
 * @method final private int                 mongoCount(array $criterion = []
 *                                               , int $skip = NULL
 *                                               , int $limit = NULL)
 * @method final private array|MongoDBCursor mongoFind(array $criterion = []
 *                                               , array $projection = []
 *                                               , array $sort_by = NULL
 *                                               , int $skip = NULL
 *                                               , int $limit = NULL
 *                                               , boolean $to_array = TRUE)
 * @method final private array|NULL          mongoFindOne(array $criterion = []
 *                                               , array $projection = [])
 * @method final private MongoId             mongoInsert(array $document)
 * @method final private array               mongoUpdate(array $criterion
 *                                               , array $update
 *                                               , boolean $multiple = FALSE)
 */
abstract class MongoDBCollection extends BaseFeature
{
// https://docs.mongodb.com/manual/core/document-validation/
// http://blog.mongodb.org/post/87200945828/6-rules-of-thumb-for-mongodb-schema-design-part-1
// https://www.mongodb.com/presentations/
// socialite-open-source-status-feed-part-2-managing-social-graph
// https://www.mongodb.com/webinars
// https://www.mongodb.com/white-papers
// https://university.mongodb.com/courses/catalog?jmp=footer&_ga=1.161627452.1639095796.1462556963
// http://snmaynard.com/2012/10/17/things-i-wish-i-knew-about-mongodb-a-year-ago/
// https://www.idontplaydarts.com/2010/07/mongodb-is-vulnerable-to-sql-injection-in-php-at-least/
// https://www.idontplaydarts.com/2011/02/mongodb-null-byte-injection-attacks/

    private $collection;

    final public function __construct()
    {
        $this->collection = Loader::db()->selectCollection(static::collectionName);
    }

    /**
     * Inserts a document into the collection, and returns the generated _id.
     * @param array $document An array or object.
     *                        If an object is used, it may not have protected or private properties.
     * @return MongoId Returns the generated _id.
     * @throws MongoException              if the inserted document is empty
     *                                     or if it contains zero-length keys.
     *                                     Attempting to insert an object with protected 
     *                                     and private properties will cause a zero-length key error.
     *                                     Inserting two elements with the same _id will causes 
     *                                     a MongoCursorException to be thrown.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final protected function add($document)
    {
        if (FALSE === is_array($document)) {
            $msg = 'Collection Operation(add) failed: $document is not an array.';
            return Kit::generateError($msg, [
                'document'  => $document,
            ]);
        }
        if (TRUE === isset($document['_id'])) {
            $msg = 'Collection operation(add) failed: can not set user-defined _id in $document.';
            return Kit::genereateError($msg, [
                'document' => $document,
            ]);
        }
        if (FALSE === isset($document['Meta'])) $document['Meta'] = [];
        $document['Meta']['CreationTime'] = new MongoDate(time());
        return $this->mongoInsert($document);
    }

    /**
     * Checks whether there is at least one document matching the criterion.
     * @param array $criterion Associative array with fields to match.
     * @return boolean Returns whether there is at least one document matching the criterion.
     * @throws MongoResultException           if the server could not execute the command
     *                                        due to an error.
     * @throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     */
    final protected function checkExistence($criterion)
    {
        $result = $this->count($criterion, NULL, 1);
        if (TRUE === Kit::checkIsError($result)) return $result;
        else return ($result > 0);
    }

    /**
     * Checks whether there is one and only one document matching the criterion.
     * @param array $criterion Associative array with fields to match.
     * @return boolean Returns whether there is one and only one document matching the criterion.
     * @throws MongoResultException           if the server could not execute the command
     *                                        due to an error.
     * @throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     */
    final protected function checkExistsOnlyOne($criterion)
    {
        $result = $this->count($criterion, NULL, 2);
        if (TRUE === Kit::checkIsError($result)) return $result;
        else return (1 === $result);
    }

    /**
     * Counts the number of documents in this collection.
     * @param array $criterion Associative array with fields to match.
     * @param int   $skip      The number of matching documents to skip before returning results.
     * @param int   $limit     The maximum number of matching documents to return.
     * @return int Returns the number of documents matching the criterion.
     * @throws MongoResultException if the server could not execute the command due to an error.
     * @throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     */
    final protected function count($criterion = [], $skip = NULL, $limit = NULL)
    {
        $criterion = self::sanitizeCriterion($criterion);
        return $this->mongoCount($criterion, $skip, $limit);
    }

    /**
     * Queries this collection, returning an array or a MongoDBCursor for the result set.
     * @param array   $criterion  Associative array with fields to match.
     * @param array   $projection Fields of the results to return. The _id field is always returned.
     * @param array   $sort_by    An array of fields by which to sort.
     *                            Each element in the array has as key the field name,
     *                            and as value either 1 for ascending sort, or -1 for descending sort.
     *                            Each result is first sorted on the first field in the array,
     *                            then (if it exists) on the second field in the array, etc.
     *                            This means that the order of the fields in the fields array
     *                            is important.
     * @param int     $skip       The number of results to skip.
     * @param int     $limit      The number of results to return.
     * @param boolean $to_array   Whether return an array instead of a MongoDBCursor.
     * @return array|MongoDBCursor Returns an array or a cursor for the results
     *                             matching the criterion.
     */
    final protected function get($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_array = FALSE)
    {
        $criterion = self::sanitizeCriterion($criterion);
        return $this->mongoFind($criterion, $projection, $sort_by, $skip, $limit, $to_array);
    }

    /**
     * Queries this collection, returning a single document.
     * If there is more than one documents matching the criterion, it will return the first one.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @param array $sort_by    An array of fields by which to sort.
     *                          Each element in the array has as key the field name,
     *                          and as value either 1 for ascending sort, or -1 for descending sort.
     *                          Each result is first sorted on the first field in the array,
     *                          then (if it exists) on the second field in the array, etc.
     *                          This means that the order of the fields in the fields array
     *                          is important.
     * @param int   $skip       The number of results to skip.
     * @return array Returns the first single document matching the criterion.
     * @throws MongoConnectionException if it cannot reach the database.
     * @throws @todo if there is no document matching the criterion, an error will be returned.
     */
    final protected function getOne($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL)
    {
        $criterion = self::sanitizeCriterion($criterion);
        $check_result = $this->checkExistence($criterion);
        if (TRUE === Kit::checkIsError($check_result)) return $check_result;
        if (FALSE === $check_result) {
            $msg = 'Collection Operation(getOne) failed: no document found.';
            return Kit::generateError($msg, [
                'criterion'  => $criterion,
                'projection' => $projection,
                'sort_by'    => $sort_by,
                'skip'       => $skip,
            ]);
        }
        // Now there must be at least one document matching the criterion.
        if (TRUE === is_null($sort_by) AND TRUE === is_null($skip)) {
            return $this->mongoFindOne($criterion, $projection);
        } else {
            $result = $this->mongoFind($criterion, $projection, $sort_by, $skip, 1, TRUE);
            if (TRUE === Kit::checkIsError($result)) return $result;
            return $result[0];
        }
    }

    /**
     * Queries this collection, returning the only single document.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array Returns the only single document matching the criterion.
     * @throws MongoConnectionException if it cannot reach the database.
     * @throws @todo                    if there is no or more than one documents matching
     *                                  the criterion, an error will be returned.
     */
    final protected function getTheOnlyOne($criterion = [], $projection = [])
    {
        $criterion = self::sanitizeCriterion($criterion);
        $check_result = $this->checkExistsOnlyOne($criterion);
        if (TRUE === Kit::checkIsError($check_result)) return $check_result;
        if (FALSE === $check_result) {
            $msg = 'Collection Operation(getTheOnlyOne) failed: no or more than one documents found.';
            return Kit::generateError($msg, [
                'criterion'  => $criterion,
                'projection' => $projection,
            ]);
        }
        return $this->getOne($criterion, $projection);
    }

    /**
     * @todo: support upsert
     * Update documents based on a given criterion.
     * @param array   $criterion Associative array with fields to match.
     * @param array   $update    The object used to update the matched documents.
     *                           This may either contain update operators
     *                           (for modifying specific fields) or be a replacement document.
     * @param boolean $upsert    If no document matches $criteria, a new document will be inserted.
     *                           If a new document would be inserted and
     *                           $new_object contains atomic modifiers (i.e. $ operators),
     *                           those operations will be applied to the $criterion parameter
     *                           to create the new document.
     *                           If $new_object does not contain atomic modifiers,
     *                           it will be used as-is for the inserted document.
     * @param boolean $multiple  All documents matching $criterion will be updated.
     *                           MongoCollection::update() has exactly the opposite behavior of
     *                           MongoCollection::remove(): it updates one document by default,
     *                           not all matching documents. It is recommended that you always
     *                           specify whether you want to update multiple documents or a single
     *                           document, as the database may change its default behavior at some
     *                           point in the future.
     * @return array Returns an array containing the status of the update.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final protected function update($criterion, $update, $multiple = FALSE)
    {
        $criterion = self::sanitizeCriterion($criterion);
        if (FALSE === $multiple) {
            $check_result = $this->checkExistsOnlyOne($criterion);
            if (TRUE === Kit::checkIsError($check_result)) return $check_result;
            if (FALSE === $check_result) {
                $msg = 'Collection Operation(update) failed: no documens found.';
                return Kit::generateError($msg, [
                    'criterion' => $criterion,
                    'update'    => $update,
                    'multiple'  => $multiple,
                ]);
            }
        }
        if (FALSE === isset($update['$set'])) $update['$set'] = [];
        $update['$set']['Meta.ModificationTime'] = new MongoDate(time());
        return $this->mongoUpdate($criterion, $update, $multiple);
    }

    /**
     * Sanitize _id in $criterion.
     * This method will not throw any exception.
     * @param array $criterion
     * @return array
     */
    final protected static function sanitizeCriterion($criterion)
    {
        if (TRUE === is_array($criterion) AND TRUE === isset($criterion['_id'])) {
            if (FALSE === is_string($criterion['_id'])) return $criterion;
            $_id = self::convertStringToMongoId($criterion['_id']);
            if (TRUE === Kit::checkIsError($_id)) return $criterion;
            $criterion['_id'] = $_id;
            return $criterion;
        } else return $criterion;
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
                $msg = 'Collection Operation(convertStringToMongoId) failed: '
                    . 'can not be parsed as a MongoId.';
                return Kit::generateError($msg, [
                    'id' => $id,
                    'exception' => Kit::extractException($e),
                ]);
            }
        } else {
            $msg = 'Collection Operation(convertStringToMongoId) failed: '
                . '$id is not a string.';
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
            $msg = 'Collection Operation(convertMongoIdToString) failed: '
                . '$id is not a MongoId.';
            return Kit::generateError($msg, [
                'id' => $id,
            ]);
        } else return strval($id);
    }

    // ================================================================================
    // Below are private methods that interact directly with MongoCollection methods.
    // ================================================================================

    /**
     * Inserts a document into the collection, and returns the generated _id.
     * Inserting two elements with the same _id will causes a MongoCursorException to be thrown.
     * @param array $document An array or object.
     *                        If an object is used, it may not have protected or private properties.
     * @return MongoId Returns the generated _id.
     * @throws MongoException              if the inserted document is empty
     *                                     or if it contains zero-length keys.
     *                                     Attempting to insert an object with protected
     *                                     and private properties will cause a zero-length key error.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final private function mongoInsert($document)
    {
        try {
            // @todo: check what if a conflict occurs due to duplicate _id
            // @todo: check what if a conflict occurs due to duplicate unique index
            // @todo: check fields in $result: ok, err, code, errmsg
            $result = $this->collection->insert($document, ['w' => 1]);
            // CAUTION: 
            // The _id field will only be added to an inserted array
            // if it does not already exist in the supplied array.
            // Even if no new document was inserted,
            // the supplied array will still have a new MongoId key.
            if (FLASE === isset($document['_id']) OR FALSE === ($document['_id'] instanceof MongoId)) {
                $msg = 'MongoDB Collection operation(insert) failed: '
                    . 'no MongoId has been generated as _id.';
                return Kit::genereateError($msg, [
                    'document' => $document,
                    'result'   => $result,
                ]);
            }
            return $document['_id'];
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Collection operation(insert) failed.', [
                'document'  => $document,
                'exception' => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Counts the number of documents in this collection.
     * @param array $criterion Associative array with fields to match.
     * @param int   $skip      The number of matching documents to skip before returning results.
     * @param int   $limit     The maximum number of matching documents to return.
     * @return int Returns the number of documents matching the criterion.
     * @throws MongoResultException           if the server could not execute the command
     *                                        due to an error.
     * @throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     */
    final private function mongoCount($criterion = [], $skip = NULL, $limit = NULL)
    {
        try {
            $options   = [];
            if (FALSE === is_null($skip))  $options['skip']  = $skip;
            if (FALSE === is_null($limit)) $options['limit'] = $limit;
            // @todo: add $hint: Index to use for the query.
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
     * @param array   $criterion  Associative array with fields to match.
     * @param array   $projection Fields of the results to return. The _id field is always returned.
     * @param array   $sort_by    An array of fields by which to sort.
     *                            Each element in the array has as key the field name,
     *                            and as value either 1 for ascending sort, or -1 for descending sort.
     *                            Each result is first sorted on the first field in the array,
     *                            then (if it exists) on the second field in the array, etc.
     *                            This means that the order of the fields in the fields array
     *                            is important.
     * @param int     $skip       The number of matching documents to skip before returning results.
     * @param int     $limit      The maximum number of matching documents to return.
     * @param boolean $to_array   Whether it should return an array instead of a MongoDBCursor.
     * @return array|MongoDBCursor Returns an array or a cursor for the results
     *                             matching the criterion.
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
     * Queries this collection, returning a single document.
     * If there are no document matching the criterion, it will return NULL.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array|NULL Returns the first single document matching the criterion or NULL.
     * @throws MongoConnectionException if it cannot reach the database.
     * @throws @todo                    if there are more than one documents matching
     *                                  the criterion, it will only return the first one.
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
     * @todo: support upsert
     * Update documents based on a given criterion.
     * @param array   $criterion Associative array with fields to match.
     * @param array   $update    The object used to update the matched documents.
     *                           This may either contain update operators
     *                           (for modifying specific fields) or be a replacement document.
     * @param boolean $upsert    If no document matches $criteria, a new document will be inserted.
     *                           If a new document would be inserted and
     *                           $new_object contains atomic modifiers (i.e. $ operators),
     *                           those operations will be applied to the $criterion parameter
     *                           to create the new document.
     *                           If $new_object does not contain atomic modifiers,
     *                           it will be used as-is for the inserted document.
     * @param boolean $multiple  All documents matching $criterion will be updated.
     *                           MongoCollection::update() has exactly the opposite behavior of
     *                           MongoCollection::remove(): it updates one document by default,
     *                           not all matching documents. It is recommended that you always
     *                           specify whether you want to update multiple documents or a single
     *                           document, as the database may change its default behavior at some
     *                           point in the future.
     * @return array Returns an array containing the status of the update.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final private function mongoUpdate($criterion, $update, $multiple = FALSE)
    {
        // support return updated document
        // return n, upserted, updatedExisting
        try {
            $options = [
                'w'        => 1,
                'upsert'   => FALSE,
                'multiple' => $multiple,
            ];
            return $this->collection->update($criterion, $update, $options);
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Collection operation(findOne) failed.', [
                'criterion' => $criterion,
                'update'    => $update,
                'multiple'  => $multiple,
                'exception' => Kit::extractException($e),
            ]);
        }
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
