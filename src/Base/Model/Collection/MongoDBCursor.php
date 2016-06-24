<?php

namespace Ilex\Base\Model\Collection;

use \Exception;
use \Iterator;
use \MongoCursor;
use \Ilex\Lib\UserException;

/**
 * Class MongoDBCursor
 * Encapsulation of basic operations of MongoCursor class.
 * @package Ilex\Base\Model\Collection
 *
 * @property private MongoCursor $cursor
 * 
 * @method public         __construct(MongoCursor $mongo_cursor)
 * 
 * @method protected         rewind()
 * @method protected array   current()
 * @method protected int     key()
 * @method protected array   next()
 * @method protected boolean hasNext()
 * @method protected boolean valid()
 * @method protected int     count()
 * @method protected array   info()
 */
final class MongoDBCursor implements Iterator
{
    // If you want to know whether a cursor returned any results
    // it is faster to use 'hasNext()' than 'count'
    
    private $cursor;

    public function __construct(MongoCursor $mongo_cursor)
    {
        $this->cursor = $mongo_cursor;
        $this->cursor->rewind(); // @TODO: check this logic.
    }

    /**
     * Resets the cursor to the beginning of the result set
     * @throws MongoConnectionException    if it cannot reach the database.
     * @throws MongoCursorTimeoutException if the timeout is exceeded.
     * @throws UserException
     */
    public function rewind()
    {
        try {
            $this->cursor->rewind();
        } catch (Exception $e) {
            throw new UserException('MongoDB Cursor operation(rewind) failed.', $this->getInfo(), $e);
        }
    }

    /**
     * Returns the current element.
     * @return array The current result document as an associative array.
     * @throws UserException if there is no result.
     */
    public function current()
    {
        $result = $this->cursor->current();
        if (TRUE === is_null($result))
            throw new UserException(
                'MongoDB Cursor operation(current) failed: there is no result.',
                $this->getInfo()
            );
        return $result;
    }

    public function key() {
        // The current result's _id as a string. If the result has no _id, its numeric index within the result set will be returned as an integer.
        return $this->cursor->key();
    }

    /**
     * Advances the cursor to the next result, and returns that result.
     * @return array Returns the next document.
     * @throws MongoConnectionException    if it cannot reach the database.
     * @throws MongoCursorTimeoutException if the timeout is exceeded.
     * @throws UserException
     */
    public function next()
    {
        try {
            return $this->cursor->next();
        } catch (Exception $e) {
            throw new UserException('MongoDB Cursor operation(next) failed.', $this->getInfo(), $e);
        }
    }

    /**
     * Checks if there are any more elements in this cursor.
     * @return boolean Returns if there is another element.
     * @throws MongoConnectionException    if it cannot reach the database.
     * @throws MongoCursorTimeoutException if the timeout is exceeded.
     * @throws UserException
     */
    public function hasNext()
    {
        try {
            return $this->cursor->hasNext();
        } catch (Exception $e) {
            throw new UserException('MongoDB Cursor operation(hasNext) failed.', $this->getInfo(), $e);
        }
    }

    public function valid() {
        return $this->cursor->valid();
    }

    /**
     * Gets information about the cursor's creation and iteration
     * This can be called before or after the cursor has started iterating.
     * If the cursor has started iterating, additional information
     * about iteration and the connection will be included.
     * @return array Returns the namespace, batch size, limit, skip, flags,
     *               query, and projected fields for this cursor.
     */
    public function info()
    {
        return $this->cursor->info();
    }

    /**
     * Counts the number of results for this query.
     * This method does not affect the state of the cursor: 
     * if you haven't queried yet, you can still apply limits, skips, etc.
     * If you have started iterating through results,
     * it will not move the current position of the cursor.
     * If you have exhasted the cursor, it will not reset it.
     * @return int The number of documents returned by this cursor's query.
     * @throws MongoConnectionException if it cannot reach the database.
     * @throws UserException
     */
    public function count()
    {
        try {
            return $this->cursor->count(TRUE);
        } catch (Exception $e) {
            throw new UserException('MongoDB Cursor operation(count) failed.', $this->getInfo(), $e);
        }
    }

    // addOption
    // awaitData
    // batchSize
    // dead
    // doQuery
// explain
    // fields
    // getReadPreference
// hint
    // immortal
    // key
    // limit
    // maxTimeMS
    // partial
    // reset
    // setFlag
    // setReadPreference
    // skip
    // slaveOkay
    // snapshot
    // sort
    // tailable
    // timeout
    // valid
}
