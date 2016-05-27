<?php

namespace Ilex\Base\Model\Data;

use \Ilex\Lib\Kit;
use \Ilex\Lib\Validator;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseData
 * @package Ilex\Base\Model\Data
 */
abstract class BaseData extends BaseModel
{

    final public function __construct()
    {

    }

    final protected static function validateInput($method_name, $input)
    {
        // if (FALSE === isset(self::$inputDataPatternList[$method_name]))
        //     return Kit::generateError("InputDataPattern is not set for method: $method_name.");
        // $pattern = self::$inputDataPatternList[$method_name];
        // return Validator::validate($pattern, $input);
        return TRUE;
    }

    // /**
    //  * @param array[] $argument_names
    //  */
    // final protected static function validateExistArguments($argument_names)
    // {
    //     if (FALSE === self::$Input->hasGet($argument_names)) {
    //         $arguments = self::$Input->get();
    //         unset($arguments['_url']);
    //         $err_info = [
    //             'missingArguments' => self::$Input->missGet($argument_names),
    //             'givenArguments'   => $arguments,
    //         ];
    //         self::$terminate('Missing arguments.', $err_info);
    //     }
    // }

    // /**
    //  * @param array[] $field_names
    //  */
    // final protected static function validateExistFields($field_names)
    // {
    //     if (FALSE === self::$Input->hasPost($field_names)) {
    //         $err_info = [
    //             'missingFields' => self::$Input->missPost($field_names),
    //             'givenFields'   => array_keys(self::$Input->post()),
    //         ];
    //         self::$terminate('Missing fields.', $err_info);
    //     }
    // }

}