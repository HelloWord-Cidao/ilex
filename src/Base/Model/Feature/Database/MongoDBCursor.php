<?php

namespace Ilex\Base\Model\Feature\Database;

use \Exception;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Feature\BaseFeature;

/**
 * Class MongoDBCursor
 * Encapsulation of basic operations of MongoCcursor class.
 * @package Ilex\Base\Model\Feature\Database
 *
 * @property private \MongoCursor $cursor
 * 
 * @method public         __construct(\MongoCursor $mongo_cursor)
 * @method public int     count()
 * @method public array   getCurrent()
 * @method public array   getInfo()
 * @method public array   getNext()
 * @method public boolean hasNext()
 * @method public         rewind()
 */
final class MongoDBCursor extends BaseFeature {
    // If you want to know whether a cursor returned any results it is faster to use 'hasNext()' than 'count'
    
    private $cursor;

    public function __construct($mongo_cursor)
    {
        $this->cursor = $mongo_cursor;
        $this->cursor->rewind(); // @todo: check this logic.
    }

    /**
     * Gets information about the cursor's creation and iteration
     * This can be called before or after the cursor has started iterating.
     * If the cursor has started iterating, additional information
     * about iteration and the connection will be included.
     * @return array Returns the namespace, batch size, limit, skip, flags,
     *               query, and projected fields for this cursor.
     */
    public function getInfo()
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
     * Throws MongoConnectionException if it cannot reach the database.
     * @return int The number of documents returned by this cursor's query.
     */
    public function count()
    {
        try {
            return $this->cursor->count(TRUE);
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Cursor operation(count) failed.', [
                'found_only' => $document,
                'info'       => $this->getInfo(),
                'exception'  => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Returns the current element.
     * @return array The current result document as an associative array.
     */
    public function getCurrent()
    {
        $result = $this->cursor->current();
        if (TRUE === is_null($result))
            return Kit::generateError('MongoDB Cursor operation(current) failed.', [
                'info' => $this->getInfo(),
            ]);
        return $result;
    }

    /**
     * Checks if there are any more elements in this cursor.
     * Throws MongoConnectionException if it cannot reach the database
     * and MongoCursorTimeoutException if the timeout is exceeded.
     * @return boolean Returns if there is another element.
     */
    public function hasNext()
    {
        try {
            return $this->cursor->hasNext();
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Cursor operation(hasNext) failed.', [
                'info'      => $this->getInfo(),
                'exception' => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Advances the cursor to the next result, and returns that result.
     * Throws MongoConnectionException if it cannot reach the database
     * and MongoCursorTimeoutException if the timeout is exceeded.
     * @return array Returns the next document.
     */
    public function getNext()
    {
        try {
            return $this->cursor->next();
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Cursor operation(next) failed.', [
                'info'      => $this->getInfo(),
                'exception' => Kit::extractException($e),
            ]);
        }
    }

    /**
     * Returns the cursor to the beginning of the result set
     * Throws MongoConnectionException if it cannot reach the database
     * and MongoCursorTimeoutException if the timeout is exceeded.
     */
    public function rewind()
    {
        try {
            $this->rewind();
        } catch (Exception $e) {
            return Kit::generateError('MongoDB Cursor operation(rewind) failed.', [
                'info'      => $this->getInfo(),
                'exception' => Kit::extractException($e),
            ]);
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
