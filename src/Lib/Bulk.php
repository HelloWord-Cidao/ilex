<?php

namespace Ilex\Lib;

use \Closure;
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

    final public function get($position)
    {
        Kit::ensureInt($position, FALSE, FALSE);
        $len = Kit::len($this->itemList);
        if ($position >= $len OR $position < -$len)
            throw new UserException("\$position($position) out of range($len).");
        if ($position < 0) $position += $len;
        return $this->itemList[$position];
    }

    final public function getTheOnlyOne()
    {
        if (1 !== $this->count())
            throw new UserException('This bulk has no or more than one items.', $this->getItemList());
        return $this->getItemList()[0];
    }

    final public function getOneRandomly()
    {
        return Kit::randomlySelect($this->getItemList(), 1)[0];
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

    final public function count()
    {
        return Kit::len($this->itemList);
    }

    public function append($item)
    {
        $this->itemList[] = $item;
        return $this;
    }

    // ==============================================================

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

    // ==============================================================
    
    final public function map(Closure $function)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $result = [];
        foreach ($this->getItemList() as $index => $item) {
            $result[] = call_user_func_array($function,
                array_merge([ $item ], $arg_list, [ $index ]));
        }
        return $result;
    }

    final public function aggregate(Closure $function, $context)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 2) $arg_list = Kit::slice($arg_list, 2); else $arg_list = [];
        foreach ($this->getItemList() as $index => $item) {
            $context = call_user_func_array($function,
                array_merge([ $item, $context ], $arg_list, [ $index ]));
        }
        return $context;
    }

    final public function filter(Closure $function)
    {
        $arg_list = func_get_args();
        if (count($arg_list) > 1) $arg_list = Kit::slice($arg_list, 1); else $arg_list = [];
        $result = [];
        foreach ($this->getItemList() as $index => $item) {
            if (TRUE === call_user_func_array($function,
                array_merge([ $item ], $arg_list, [ $index ]))) {
                $result[] = $item;
            }
        }
        return $this->setItemList($result);
    }

    final public function randomlySelect($num)
    {
        return $this->setItemList(Kit::randomlySelect($this->getItemList(), $num));
    }

    final public function shuffle()
    {
        return $this->setItemList(Kit::shuffled($this->getItemList()));
    }

    final public function sort(Closure $function, $direction = 1)
    {
        
        return $this->setItemList(Kit::sorted($function, $this->getItemList(), $direction));
    }

    final public function reverse()
    {
        return $this->setItemList(Kit::reversed($this->getItemList()));
    }

    final public function slice($start, $length)
    {
        return $this->setItemList(Kit::slice($this->getItemList(), $start, $length));
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
}