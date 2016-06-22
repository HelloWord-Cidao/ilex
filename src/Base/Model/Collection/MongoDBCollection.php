<?php

namespace Ilex\Base\Model\Collection;

use \Exception;
use \MongoId;
use \MongoDate;
use \MongoCollection;
use \Ilex\Core\Loader;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Model\BaseModel;
use \Ilex\Base\Model\Collection\MongoDBCursor;

/**
 * Class MongoDBCollection
 * Encapsulation of basic operations of MongoCollection class.
 * @package Ilex\Base\Model\Collection
 *
 * @property private MongoCollection $collection
 *
 * @method       public                               __construct()
 * @method       protected        MongoId             addOne(array $document)
 * @method final protected        boolean             checkExistence(array $criterion)
 * @method final protected        boolean             checkExistsOnlyOnce(array $criterion)
 * @method final protected        string              convertMongoIdToString(MongoId $id)
 * @method final protected        MongoId             convertStringToMongoId(string $id)
 * @method final protected        int                 count(array $criterion = []
 *                                                        , int $skip = NULL
 *                                                        , int $limit = NULL)
 * @method final protected        array|MongoDBCursor getMulti(array $criterion = []
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
 * @method final protected        array               recoverCriterion(array $criterion)
 * @method final protected        array               sanitizeCriterion(array $criterion)
 * @method final protected        array               updateOne(array $criterion
 *                                                        , array $update)
 * 
 * @method final protected int                 mongoCount(array $criterion = []
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL)
 * @method final protected array|MongoDBCursor mongoFind(array $criterion = []
 *                                                 , array $projection = []
 *                                                 , array $sort_by = NULL
 *                                                 , int $skip = NULL
 *                                                 , int $limit = NULL
 *                                                 , boolean $to_array = TRUE)
 * @method final protected array|NULL          mongoFindOne(array $criterion = []
 *                                                 , array $projection = [])
 * @method final protected MongoId             mongoInsert(array $document)
 * @method final protected array               mongoUpdate(array $criterion
 *                                                 , array $update
 *                                                 , boolean $multiple = FALSE)
 */
abstract class MongoDBCollection extends BaseModel
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

    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'getCollectionName',
        ],
        self::V_PROTECTED => [
            // 'addMulti',
            'addOne',
            'checkExistence',
            'ensureExistence',
            'checkExistsOnlyOnce',
            'ensureExistsOnlyOnce',
            // 'convertMongoIdToString',
            // 'convertStringToMongoId',
            'count',
            'getMulti',
            'getOne',
            'getTheOnlyOne',
            // 'recoverCriterion',
            // 'sanitizeCriterion',
            'updateMulti',
            // 'updateOne',
            'updateTheOnlyOne',
        ],
    ];

    protected $collectionName = NULL;
    private $collection     = NULL;

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

    final protected function getCollectionName()
    {
        return $this->collectionName;
    }

    final protected function ensureInitialized()
    {
        $collection_name = $this->collectionName;
        if (FALSE === isset($this->collection)
            OR FALSE === $this->collection instanceof MongoCollection)
            throw new UserException("This collection($collection_name) has not been initialized.");
    }

    final protected function ensureDocumentHasNoId($document)
    {
        Kit::ensureArray($document);
        if (TRUE === isset($document['_id']))
            throw new UserException('$document should have no _id field.', $document);
    }

    final protected function ensureCriterionHasProperId($criterion)
    {
        Kit::ensureArray($criterion);
        if (TRUE === isset($criterion['_id']) AND FALSE === $criterion['_id'] instanceof MongoId)
            throw new UserException('$criterion has improper _id.', $criterion);
    }

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
    final protected function addOne($document)
    {
        // Kit::ensureDict($document); // @CAUTION
        Kit::ensureArray($document);
        $this->call('ensureDocumentHasNoId', $document);
        if (FALSE === isset($document['Meta']))
            $document['Meta'] = [];
        $document['Meta']['CreationTime'] = new MongoDate();
        
        $result = $this->call('mongoInsert', $document);
        if (FALSE === (bool)$result['status']['ok'] OR TRUE === isset($result['status']['err']))
            throw new UserException('MongoDBCollection insert operation failed.', [ $result, $document ]);
        if (FLASE === isset($result['document']['_id'])
            OR FALSE === ($result['document']['_id'] instanceof MongoId)
        ) {
            throw new UserException('No _id has been generated in the inserted document.', $result);
        }

        return [
            '_id'    => $result['document']['_id'],
            'status' => $result['status'],
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        return ($this->call('count', $criterion, NULL, 1) > 0);
    }

    final protected function ensureExistence($criterion)
    {
        if (FALSE === $this->call('checkExistence', $criterion))
            throw new UserException('$criterion does not exist.', $criterion);
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        return (1 === $this->call('count', $criterion, NULL, 2));
    }

    final protected function ensureExistsOnlyOnce($criterion)
    {
        if (FALSE === $this->call('checkExistsOnlyOnce', $criterion))
            throw new UserException('$criterion does not exist only once.', $criterion);
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        return $this->call('mongoCount', $criterion, $skip, $limit);
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        return $this->call('mongoFind', $criterion, $projection, $sort_by, $skip, $limit, $to_array);
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        $this->call('ensureExistsOnlyOnce', $criterion);
        return $this->call('getOne', $criterion, $projection);
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
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        $this->call('ensureExistence', $criterion);
        // Now there must be at least one document matching the criterion.
        if (TRUE === is_null($sort_by) AND TRUE === is_null($skip))
            return $this->call('mongoFindOne', $criterion, $projection);
        else return $this->call('mongoFind', $criterion, $projection, $sort_by, $skip, 1, TRUE)[0];
    }

    /**
     * @TODO: support upsert
     * Update the only one document based on a given criterion.
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
    final protected function updateTheOnlyOne($criterion, $update, $is_document)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($update); // @CAUTION
        Kit::ensureArray($update);
        Kit::ensureBoolean($is_document);
        $criterion = $this->call('sanitizeCriterion', $criterion);
        $this->call('ensureCriterionHasProperId', $criterion);
        $this->call('ensureExistsOnlyOnce', $criterion);
        if (TRUE === $is_document) {
            $this->call('ensureDocumentHasNoId', $update);
            if (FALSE === isset($update['Meta']) OR FALSE === isset($update['Meta']['CreationTime']))
                throw new UserException('$update has no Meta or Meta.CreationTime field as a document.', $update);
            $update['Meta']['ModificationTime'] = new MongoDate();
        } else {
            if (FALSE === isset($update['$set'])) $update['$set'] = [];
            $update['$set']['Meta.ModificationTime'] = new MongoDate();
            if (TRUE === isset($update['$set']['_id']))
                throw new UserException('$update should not set the _id field.', $update);
        }
        $status = $this->call('mongoUpdate', $criterion, $update, FALSE);
        if (FALSE === (bool)$status['ok'] OR TRUE === isset($status['err']) OR 1 !== $status['n'])
            throw new UserException('MongoDBCollection update operation failed.',
                [ $status, $criterion, $update ]);
        return $status;
    }

    /**
     * Sanitize _id in $criterion.
     * This method will not throw any exception.
     * @param array $criterion
     * @return array
     */
    final protected function sanitizeCriterion($criterion)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        if (TRUE === isset($criterion['_id'])) {
            if (FALSE === Kit::isString($criterion['_id'])) return $criterion;
            try {
                $_id = $this->call('convertStringToMongoId', $criterion['_id']);
            } catch (Exception $e) {
                return $criterion;
            }
            $criterion['_id'] = $_id;
        }
        return $criterion;
    }

    /**
     * Converts a string to a MongoId.
     * @param string $string
     * @return MongoId
     * @throws UserException if $string is not a string or can not be parsed as a MongoId.
     */
    final protected function convertStringToMongoId($string)
    {
        Kit::ensureString($string);
        try {
            return new MongoId($string);
        } catch (Exception $e) {
            throw new UserException('Can not be parsed as a MongoId.', $string);
        }
    }

    /**
     * Converts a MongoId to a string.
     * @param MongoId $mongo_id
     * @return string
     * @throws UserException if $mongo_id is not a MongoId.
     */
    final protected function convertMongoIdToString($mongo_id)
    {
        if (FALSE === ($mongo_id instanceof MongoId))
            throw new UserException('$mongo_id is not a MongoId.', $mongo_id);
        else return strval($mongo_id);
    }

    /**
     * Recovers '.' from '_' in a MongoDB criterion keys.
     * @param array $criterion
     * @return array
     */
    final protected function recoverCriterion($criterion)
    {
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        foreach ($criterion as $key => $value) {
            unset($criterion[$key]);
            $criterion[str_replace('_', '.', $key)] = $value;
        }
        return $criterion;
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
     * @throws UserException               if no _id has been generated in the inserted document.
     */
    final protected function mongoInsert($document)
    {
        $this->call('ensureInitialized');
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
        $status = $this->collection->insert($document, ['w' => 1]);
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
    final protected function mongoCount($criterion = [], $skip = NULL, $limit = NULL)
    {
        $this->call('ensureInitialized');
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        $options = [];
        if (FALSE === is_null($skip))  $options['skip']  = $skip;
        if (FALSE === is_null($limit)) $options['limit'] = $limit;
        // @TODO: add $hint: Index to use for the query.
        return $this->collection->count($criterion, $options);
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
    final protected function mongoFind($criterion = [], $projection = [], $sort_by = NULL
        , $skip = NULL, $limit = NULL, $to_array = FALSE)
    {
        $this->call('ensureInitialized');
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        // Kit::ensureDict($sort_by, TRUE); // @CAUTION
        Kit::ensureArray($sort_by, TRUE);
        Kit::ensureInt($skip, TRUE);
        Kit::ensureInt($limit, TRUE);
        Kit::ensureBoolean($to_array);
        $cursor = $this->collection->find($criterion, $projection);
        if (FALSE === is_null($sort_by)) $cursor = $cursor->sort($sort_by);
        if (FALSE === is_null($skip))    $cursor = $cursor->skip($skip);
        if (FALSE === is_null($limit))   $cursor = $cursor->limit($limit);
        if (TRUE === $to_array) return iterator_to_array($cursor, FALSE);
        else new MongoDBCursor($cursor);
    }

    /**
     * Queries this collection, returning a single document.
     * If there are no document matching the criterion, it will return NULL.
     * @param array $criterion  Associative array with fields to match.
     * @param array $projection Fields of the results to return. The _id field is always returned.
     * @return array|NULL Returns the first single document matching the criterion or NULL.
     * @throws MongoConnectionException if it cannot reach the database.
     */
    final protected function mongoFindOne($criterion = [], $projection = [])
    {
        $this->call('ensureInitialized');
        // Kit::ensureDict($criterion); // @CAUTION
        Kit::ensureArray($criterion);
        // Kit::ensureDict($projection); // @CAUTION
        Kit::ensureArray($projection);
        return $this->collection->findOne($criterion, $projection);
    }

    /**
     * @TODO: support upsert
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
    final protected function mongoUpdate($criterion, $update, $multiple = FALSE)
    {
        $this->call('ensureInitialized');
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
        // @TODO: include updated documents?
        return $this->collection->update($criterion, $update, $options);
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
