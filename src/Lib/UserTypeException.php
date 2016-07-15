<?php

namespace Ilex\Lib;

use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * @todo: method arg type validate
 * Class UserTypeException
 * An user-defined type exception.
 * @package Ilex\Lib
 */
final class UserTypeException extends UserException
{
    private $variable;
    private $variableType;
    private $expectedTypeList;

    /**
     * @param string    $message
     * @param mixed     $detail
     * @param Exception $previous
     * @param int       $code
     */
    final public function __construct($variable, $expected_type_list)
    {
        if (FALSE === is_array($expected_type_list)) {
            $expected_type_list = [ $expected_type_list ];
        }
        foreach ($expected_type_list as $expected_type) {
            if (FALSE === Kit::isValidType($expected_type))
                throw new UserException('Invalid type.', $expected_type);
        }
        $this->variable = $variable;
        $variable_type = $this->variableType = Kit::type($variable, TRUE);
        $this->expectedTypeList = $expected_type_list;
        $expected_type_string = Kit::join(' or ', $expected_type_list);
        $message = "Invalid type(${variable_type}), ${expected_type_string} is expected.";
        $detail = [
            'variable'           => $variable,
            'variable_type'      => $variable_type,
            'expected_type_list' => $expected_type_list,
        ];
        parent::__construct($message, $detail);
    }

    final public function getVariable()
    {
        return $this->variable;
    }

    final public function getVariableType()
    {
        return $this->variableType;
    }

    final public function getExpectedType()
    {
        return $this->expectedType;
    }
}