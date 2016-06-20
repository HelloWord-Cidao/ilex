<?php

namespace Ilex\Lib;

use \Exception;

/**
 * @todo: method arg type validate
 * Class UserException
 * An user-defined exception, supporting recording detail information.
 * @package Ilex\Lib
 *
 * @property private mixed $detail
 *
 * @method public       __construct(string $message, mixed $detail = NULL
 *                          , Exception $previous = NULL, int $code = 0)
 * @method public mixed getDetail()
 */
class UserException extends Exception
{
    // http://php.net/manual/en/language.exceptions.php
    // Using a return statement inside a finally block will override any other return statement 
    // or thrown exception from the try block and all defined catch blocks.
    // Code execution in the parent stack will continue as if the exception was never thrown.  
    // Frankly this is a good design decision because it means I can optionally dismiss 
    // all thrown exceptions from 1 or more catch blocks in one place, 
    // without having to nest my whole try block inside an additional 
    // (and otherwise needless) try/catch block.
    // When using finally keep in mind that when a exit/die statement is used in the catch block
    // it will NOT go through the finally block. 
    // There's some inconsistent behaviour associated with PHP 5.5.3's finally and return statements.
    // If a method returns a variable in a try block (e.g. return $foo;),
    // and finally modifies that variable, the /modified/ value is returned.
    // However, if the try block has a return that has to be evaluated in-line (e.g. return $foo+0;),
    // finally's changes to $foo will /not/ affect the return value.
    
    private $detail;

    /**
     * @param string    $message
     * @param mixed     $detail
     * @param Exception $previous
     * @param int       $code
     */
    public function __construct($message, $detail = NULL, $previous = NULL, $code = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->detail = $detail;
    }

    /**
     * @return mixed
     */
    public function getDetail()
    {
        return $this->detail;
    }
}