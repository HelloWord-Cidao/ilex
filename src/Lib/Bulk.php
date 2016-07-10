<?php

namespace Ilex\Lib;

use \Iterator;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class Bulk
 * Base class of bulk models of Ilex.
 * @package Ilex\Lib
 */
class Bulk implements Iterator
{

    private $position = 0;
    private $itemList = [];

    public function __construct($item_list)
    {
        Kit::ensureArray($item_list); // @CAUTION
        $this->position = 0;
        $this->itemList = $item_list;
    }

    final public function rewind() {
        $this->position = 0;
    }

    final public function current() {
        return $this->itemList[$this->position];
    }

    final public function key() {
        return $this->position;
    }

    final public function next() {
        ++$this->position;
    }

    final public function valid() {
        return TRUE === isset($this->itemList[$this->position]);
    }

    final public function getItemList()
    {
        return $this->itemList;
    }

    final protected function setItemList($item_list)
    {
        $this->itemList = $item_list;
        return $this;
    }

    final public function count()
    {
        return Kit::len($this->itemList);
    }

    final public function first()
    {
        if (0 === $this->count())
            throw new UserException('Failed to get the first item, because this bulk is empty.', $this);
        return $this->itemList[0];
    }

    final public function last()
    {
        if (0 === $this->count())
            throw new UserException('Failed to get the last item, because this bulk is empty.', $this);
        return Kit::last($this->itemList);
    }

}