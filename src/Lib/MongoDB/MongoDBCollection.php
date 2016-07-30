<?php

namespace Ilex\Lib\MongoDB;

use \Exception;
use \MongoId;
use \MongoDate;
use \MongoCollection;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class MongoDBCollection
 * Encapsulation of basic operations of MongoCollection class.
 * @package Ilex\Lib\MongoDB
 *
 * @property private MongoCollection $collection
 *
 * @method       public                        __construct()
 * @method final protected array               addOne(array $document)
 * @method final protected boolean             checkExistence(array $criterion)
 * @method final protected boolean             checkExistsOnlyOnce(array $criterion)
 * @method final protected int                 count(array $criterion = []
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL)
 * @method final protected array|MongoDBCursor getMulti(array $criterion = []
 *                                                 , array $projection = []
 *                                                 , array $sort_by = NULL
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL
 *                                                 , boolean $to_array = TRUE)
 * @method final protected array               getOne(array $criterion = []
 *                                                 , array $projection = []
 *                                                 , array $sort_by = NULL
 *                                                 , int $skip = NULL)
 * @method final protected array               getTheOnlyOne(array $criterion = [], array $projection = [])
 * @method final protected array               updateTheOnlyOne(array $criterion, array $update)
 * 
 * @method final private int                 mongoCount(array $criterion = []
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL)
 * @method final private array|MongoDBCursor mongoFind(array $criterion = []
 *                                                 , array $projection = []
 *                                                 , array $sort_by = NULL
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL
 *                                                 , boolean $to_array = TRUE)
 * @method final private array|NULL          mongoFindOne(array $criterion = [], array $projection = [])
 * @method final private array               mongoInsert(array $document)
 * @method final private array               mongoUpdate(array $criterion, array $update
 *                                                 , boolean $multiple = FALSE)
 */
class MongoDBCollection
{
// https://docs.mongodb.com/manual/core/document-validation/
// http://blog.mongodb.org/post/87200945828/6-rules-of-thumb-for-mongodb-schema-design-part-1
// https://www.mongodb.com/presentations/
// socialite-open-source-status-feed-part-2-managing-social-graph
// https://www.mongodb.com/webinars
// https://www.mongodb.com/white-papers
// https://university.mongodb.com/courses/catalog?jmp=footer&_ga=1.161627452.1639095796.1462556963
// http://snmaynard.com/2012/10/17/things-i-wish-i-knew-about-mongodb-a-year-ago/
// https://www.idontplaydarts.com/2011/02/mongodb-null-byte-injection-attacks/
// https://www.idontplaydarts.com/2010/07/mongodb-is-vulnerable-to-sql-injection-in-php-at-least/

    const OP_INSERT = 'INSERT';
    const OP_UPDATE = 'UPDATE';
    const OP_REMOVE = 'REMOVE';

    protected $collectionName = NULL;

    private        $collection = NULL;
    private static $isChanged  = FALSE;
    private static $history    = [];

    final public static function rollback()
    {
        if (0 === Kit::len(self::$history)) return FALSE;
        $exists_document_not_rollbacked = FALSE;
        foreach (Kit::reversed(self::$history) as $operation) {
            if (FALSE === $operation['CanBeRollbacked']) {
                $exists_document_not_rollbacked = TRUE;
                continue;
            }
            if (self::OP_INSERT === $operation['Type']) {
                $operation['Collection']->removeTheOnlyOne([ '_id' => $operation['Id'] ], TRUE);
            } elseif (self::OP_UPDATE === $operation['Type']) {
                $operation['Collection']->updateTheOnlyOne([ '_id' => $operation['Document']['_id'] ],
                    $operation['Document'], TRUE);
            } elseif (self::OP_REMOVE === $operation['Type']) {
                $operation['Collection']->addOne($operation['Document'], TRUE);
            }
        }
        if (FALSE === $exists_document_not_rollbacked) self::$isChanged = FALSE;
        return TRUE;
    }

    final public static function getHistory()
    {
        return self::$history;
    }

    final public static function isChanged()
    {
        return self::$isChanged;
    }

    protected function __construct($collection_name)
    {
        Kit::ensureString($collection_name);
        try {
            $this->collectionName = $collection_name;
            $this->collection = Loader::loadMongoDB()->selectCollection($collection_name);
        } catch (Exception $e) {
            throw new UserException('Initializing collection failed.', $collection_name, $e);
        }
    }

    final public function getCollectionName()
    {
        return $this->collectionName;
    }

    final private function ensureInitialized()
    {
        $collection_name = $this->collectionName;
        if (FALSE === isset($this->collection)
            OR FALSE === $this->collection instanceof MongoCollection)
            throw new UserException("This collection($collection_name) has not been initialized.");
    }

    
    // ================================================================================
    

    /**
     * Inserts a document into the collection, and returns the generated _id.
     * @param array $document An array or object.
     *                        If an object is used, it may not have protected or private properties.
     * @return array Returns the generated _id and the operation status.
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
    final protected function addOne($document, $is_rollback = FALSE, $can_be_rollbacked = TRUE)
    {
        $collection_name = $this->collectionName;
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        Kit::ensureBoolean($is_rollback);
        Kit::ensureBoolean($can_be_rollbacked);
        if (FALSE === $is_rollback)
            $this->ensureDocumentHasNoId($document);
        if (FALSE === isset($document['Meta']))
            $document['Meta'] = [];
        $document['Meta']['CreationTime'] = Kit::now();
        
        $result = $this->mongoInsert($document);
        if (FALSE === (bool)$result['status']['ok'] OR TRUE === isset($result['status']['err']))
            throw new UserException("<${collection_name}>MongoDBCollection insert operation failed.",
                [ $result, $document ]);
        if (FLASE === isset($result['document']['_id'])
            OR FALSE === ($result['document']['_id'] instanceof MongoId)
        ) {
            throw new UserException("<${collection_name}>No _id has been generated in the inserted document.",
                $result);
        }
        self::$history[] = [
            'Collection'      => $this,
            'CollectionName'  => $this->getCollectionName(),
            'Type'            => self::OP_INSERT,
            'Id'              => $result['document']['_id'],
            'Document'        => $result['document'],
            'CanBeRollbacked' => $can_be_rollbacked,
        ];
        return [
            'document' => $result['document'],
            'status'   => $result['status'],
        ];
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
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        $this->ensureCriterionHasProperId($criterion);
        return ($this->count($criterion, NULL, 1) > 0);
    }

    final protected function ensureExistence($criterion)
    {
        $collection_name = $this->collectionName;
        if (FALSE === $this->checkExistence($criterion))
            throw new UserException("<${collection_name}>\$criterion does not exist.", $criterion);
    }

    /**
     * Checks whether there is one and only one document matching the criterion.
     * @param array $criterion Associative array with fields to match.
     * @return boolean Returns whether there is one and only one document matching the criterion.
     * @throws MongoResultException           if the server could not execute the command
     *                                        due to an error.
     * @throws MongoExecutionTimeoutException if command execution was terminated due to maxTimeMS.
     */
    final protected function checkExistsOnlyOnce($criterion)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        $this->ensureCriterionHasProperId($criterion);
        return (1 === $this->count($criterion, NULL, 2));
    }

    final protected function ensureExistsOnlyOnce($criterion)
    {
        $collection_name = $this->collectionName;
        Kit::ensureArray($criterion);
        $this->ensureCriterionHasProperId($criterion);
        if (0 === $this->count($criterion, NULL, 2))
            throw new UserException("<${collection_name}>\$criterion does not exist.", $criterion);
        elseif ($this->count($criterion, NULL, 2) > 1)
            throw new UserException("<${collection_name}>\$criterion exists more than once.", $criterion);
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
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        $this->ensureCriterionHasProperId($criterion);
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
    final protected function getMulti($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_array = FALSE)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        // Kit::ensureDict($sort_by, TRUE); // @CAUTION
        Kit::ensureArray($sort_by, TRUE);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        Kit::ensureBoolean($to_array);
        $this->ensureCriterionHasProperId($criterion);
        return $this->mongoFind($criterion, $projection, $sort_by, $skip, $limit, $to_array);
    }

    /**
     * Queries this collection, returning the only single document.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array Returns the only single document matching the criterion.
     * @throws MongoConnectionException if it cannot reach the database.
     * @throws UserException            if there is no or more than one documents matching
     *                                  the criterion, an error will be returned.
     */
    final protected function getTheOnlyOne($criterion = [], $projection = [])
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        $this->ensureCriterionHasProperId($criterion);
        $this->ensureExistsOnlyOnce($criterion);
        return $this->getOne($criterion, $projection);
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
     * @throws UserException            if there is no document matching the criterion.
     */
    final protected function getOne($criterion = [], $projection = [], $sort_by = NULL, $skip = NULL)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        // Kit::ensureDict($sort_by, TRUE); // @CAUTION
        Kit::ensureArray($sort_by, TRUE);
        Kit::ensureInt($skip, TRUE);
        $this->ensureCriterionHasProperId($criterion);
        $this->ensureExistence($criterion);
        // Now there must be at least one document matching the criterion.
        if (TRUE === is_null($sort_by) AND TRUE === is_null($skip))
            return $this->mongoFindOne($criterion, $projection);
        else return $this->mongoFind($criterion, $projection, $sort_by, $skip, 1, TRUE)[0];
    }

    /**
     * Update the only one document based on a given criterion.
     * @param array   $criterion   Associative array with fields to match.
     * @param array   $update      The object used to update the matched documents.
     *                             This may either contain update operators
     *                             (for modifying specific fields) or be a replacement document.
     * @param boolean $is_document Whether the $update is a document to replace the old one.
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
    final protected function updateTheOnlyOne($criterion, $update, $is_rollback = FALSE, $can_be_rollbacked = TRUE)
    {
        $collection_name = $this->collectionName;
        // Kit::ensureDict($new_document); // @CAUTION
        $new_document = Kit::ensureArray($update); // @CAUTION
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureBoolean($is_document);
        Kit::ensureBoolean($is_rollback);
        Kit::ensureBoolean($can_be_rollbacked);
        $this->ensureCriterionHasProperId($criterion);
        $document = $this->getTheOnlyOne($criterion);
        // if (TRUE === $is_document) {
            if (FALSE === $is_rollback)
                $this->ensureDocumentHasNoId($new_document);
            if (FALSE === isset($new_document['Meta']) 
                OR FALSE === isset($new_document['Meta']['CreationTime'])){
                $msg = "<${collection_name}>\$new_document has no Meta or Meta.CreationTime field as a document.";
                throw new UserException($msg, $new_document);
            }
            if (FALSE === $is_rollback)
                $new_document['Meta']['ModificationTime'] = Kit::now();
        // } else {
            // if (FALSE === isset($update['$set'])) $update['$set'] = [];
            // $update['$set']['Meta.ModificationTime'] = Kit::now();
            // if (TRUE === isset($update['$set']['_id']))
                // throw new UserException("<${collection_name}>\$update should not set the _id field.", $update);
        // }
        $status = $this->mongoUpdate($criterion, $new_document, FALSE);
        if (FALSE === $this->validateOperationStatus($status)) {
            if (FALSE === $is_rollback) {
                $msg = "<${collection_name}>MongoDBCollection update operation failed.";
                throw new UserException($msg, [ $status, $criterion, $new_document ]);
            } else {
                // @TODO
            }
        }
        self::$history[] = [
            'Collection'      => $this,
            'CollectionName'  => $this->getCollectionName(),
            'Type'            => self::OP_UPDATE,
            'Criterion'       => $criterion,
            'Document'        => $document,
            'Update'          => $new_document,
            'CanBeRollbacked' => $can_be_rollbacked
        ];
        // return $status;
        return $new_document; // @CAUTION
    }

    /**
     * Remove the only one document from this collection based on a given criterion.
     * @param array $criterion Associative array with fields to match.
     * @return array Returns an array containing the status of the removal.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final protected function removeTheOnlyOne($criterion, $is_rollback = FALSE, $can_be_rollbacked = TRUE)
    {
        $collection_name = $this->collectionName;
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        Kit::ensureBoolean($is_rollback);
        Kit::ensureBoolean($can_be_rollbacked);
        $this->ensureCriterionHasProperId($criterion);
        $document = $this->getTheOnlyOne($criterion);
        $status = $this->mongoRemove($criterion, FALSE);
        if (FALSE === $this->validateOperationStatus($status)) {
            $msg = "<${collection_name}>MongoDBCollection remove operation failed.";
            throw new UserException($msg, [ $status, $criterion ]);
        }
        self::$history[] = [
            'Collection'      => $this,
            'CollectionName'  => $this->getCollectionName(),
            'Type'            => self::OP_REMOVE,
            'Criterion'       => $criterion,
            'Document'        => $document,
            'CanBeRollbacked' => $can_be_rollbacked,
        ];
        return $status;
    }


    // ================================================================================


    final private function ensureDocumentHasNoId($document)
    {
        $collection_name = $this->collectionName;
        Kit::ensureArray($document);
        if (TRUE === isset($document['_id']))
            throw new UserException("<${collection_name}>\$document should have no _id field.", $document);
    }

    final private function ensureCriterionHasProperId($criterion)
    {
        $collection_name = $this->collectionName;
        Kit::ensureArray($criterion);
        if (TRUE === isset($criterion['_id']) AND (
            (TRUE === Kit::isArray($criterion['_id']) AND
                TRUE === isset($criterion['_id']['$in']) AND
                FALSE === Kit::isArray($criterion['_id']['$in'])) 
            OR
            (TRUE === Kit::isArray($criterion['_id']) AND
                TRUE === isset($criterion['_id']['$ne']) AND
                FALSE === $criterion['_id']['$ne'] instanceof MongoId) 
            OR
            (FALSE === Kit::isArray($criterion['_id']) AND
                FALSE === $criterion['_id'] instanceof MongoId)
            )
        ) throw new UserException("<${collection_name}>\$criterion has improper _id.", $criterion);
    }

    final private function validateOperationStatus($status)
    {
        return FALSE === (
            FALSE === (bool)$status['ok'] 
            OR TRUE === isset($status['err']) 
            OR 1 !== $status['n']
        );
    }

    
    // ================================================================================
    // Below are private methods that interact directly with MongoCollection methods.
    // ================================================================================

    /**
     * Inserts a document into the collection, and returns the generated _id.
     * Inserting two elements with the same _id will causes a MongoCursorException to be thrown.
     * @param array $document An array or object.
     *                        If an object is used, it may not have protected or private properties.
     * @return 
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
     * @throws UserException               if no _id has been generated in the inserted document.
     */
    final private function mongoInsert($document)
    {
        $this->ensureInitialized();
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        // @TODO: check what if a conflict occurs due to duplicate _id
        // @TODO: check what if a conflict occurs due to duplicate unique index
        // @TODO: check fields in $result: ok, err, code, errmsg
        // CAUTION: 
        // The _id field will only be added to an inserted array
        // if it does not already exist in the supplied array.
        // Even if no new document was inserted,
        // the supplied array will still have a new MongoId key.
        try {
            $status = $this->collection->insert($document, ['w' => 1]);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [ 'Document' => $document ], $e);
        }
        self::$isChanged = TRUE;
        return [
            'document' => $document,
            'status'   => $status,
        ];
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
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        $options = [];
        if (FALSE === is_null($skip))  $options['skip']  = $skip;
        if (FALSE === is_null($limit)) $options['limit'] = $limit;
        // @TODO: add $hint: Index to use for the query.
        try {
            return $this->collection->count($criterion, $options);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [
                'Criterion' => $criterion,
                'Options'   => $options,
            ], $e);
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
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        // Kit::ensureDict($sort_by, TRUE); // @CAUTION
        Kit::ensureArray($sort_by, TRUE);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        Kit::ensureBoolean($to_array);
        try {
            $cursor = $this->collection->find($criterion, $projection);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [
                'Criterion'  => $criterion,
                'Projection' => $projection,
            ], $e);
        }
        if (FALSE === is_null($sort_by)) $cursor = $cursor->sort($sort_by);
        if (FALSE === is_null($skip))    $cursor = $cursor->skip($skip);
        if (FALSE === is_null($limit))   $cursor = $cursor->limit($limit);
        if (TRUE === $to_array) return iterator_to_array($cursor, FALSE);
        else return new MongoDBCursor($cursor);
    }

    /**
     * Queries this collection, returning a single document.
     * If there are no document matching the criterion, it will return NULL.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array|NULL Returns the first single document matching the criterion or NULL.
     * @throws MongoConnectionException if it cannot reach the database.
     */
    final private function mongoFindOne($criterion = [], $projection = [])
    {
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        try {
            return $this->collection->findOne($criterion, $projection);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [
                'Criterion'  => $criterion,
                'Projection' => $projection,
            ], $e);
        }
    }

    /**
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
     * @param boolean $multiple  If set to TRUE, all documents matching $criterion will be updated.
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
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($update); // @CAUTION
        Kit::ensureArray($update);
        Kit::ensureBoolean($multiple);
        $options = [
            'w'        => 1,
            'upsert'   => FALSE,
            'multiple' => $multiple,
        ];
        // @TODO: check returns n, upserted, updatedExisting
        try {
            $status = $this->collection->update($criterion, $update, $options);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [
                'Criterion' => $criterion,
                'Update'    => $update,
                'Options'   => $options,
            ], $e);
        }
        self::$isChanged = TRUE;
        return $status;
    }

    /**
     * Remove documents from this collection.
     * @param array   $criterion Associative array with fields to match.
     * @param boolean $multiple  If set to TRUE, all documents matching $criterion will be removed.
     * @return array Returns an array containing the status of the removal.
     * @throws MongoCursorException        if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one
     *                                     and the operation takes longer than MongoCursor::$timeout
     *                                     milliseconds to complete.
     *                                     This does not kill the operation on the server,
     *                                     it is a client-side timeout.
     *                                     The operation in MongoCollection::$wtimeout
     *                                     is milliseconds.
     */
    final private function mongoRemove($criterion, $multiple = TRUE)
    {
        $this->ensureInitialized();
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        Kit::ensureBoolean($multiple);
        $options = [
            'w'       => 1,
            'justOne' => (FALSE === $multiple),
        ];
        try {
            $status = $this->collection->remove($criterion, $options);
        } catch (Exception $e) {
            throw new UserException($e->getMessage(), [
                'Criterion' => $criterion,
                'Options'   => $options,
            ], $e);
        }
        self::$isChanged = TRUE;
        return $status;
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
