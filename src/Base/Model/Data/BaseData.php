<?php

namespace Ilex\Base\Model\Data;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Kit;
use \Ilex\Lib\Validator;

/**
 * Class BaseData
 * @package Ilex\Base\Model\Data
 * 
 * @method protected array tryFetchArguments(array[] $argument_names)
 * @method protected array tryFetchData(array[] $field_names)
 * @method protected array fetchArguments(array[] $argument_names)
 * @method protected array fetchData(array[] $field_names)
 * @method protected       validateExistArguments(array[] $argument_names)
 * @method protected       validateExistFields(array[] $field_names)
 */
class BaseData extends BaseModel
{

    public function __construct()
    {

    }

    public function validateInput($method_name, $input)
    {
        if (FALSE === isset($this->inputDataPatternList[$method_name]))
            return Kit::generateErrorInfo("InputDataPattern is not set for method: $method_name.");
        $pattern = $this->inputDataPatternList[$method_name];
        return Validator::validate($pattern, $input);
    }

    /**
     * @param array[] $argument_names
     */
    protected function validateExistArguments($argument_names)
    {
        if (FALSE === $this->Input->hasGet($argument_names)) {
            $arguments = $this->Input->get();
            unset($arguments['_url']);
            $err_info = [
                'missingArguments' => $this->Input->missGet($argument_names),
                'givenArguments'   => $arguments,
            ];
            $this->terminate('Missing arguments.', $err_info);
        }
    }

    /**
     * @param array[] $field_names
     */
    protected function validateExistFields($field_names)
    {
        if (FALSE === $this->Input->hasPost($field_names)) {
            $err_info = [
                'missingFields' => $this->Input->missPost($field_names),
                'givenFields'   => array_keys($this->Input->post()),
            ];
            $this->terminate('Missing fields.', $err_info);
        }
    }

}