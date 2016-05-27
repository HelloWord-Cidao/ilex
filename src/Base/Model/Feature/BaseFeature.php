<?php

namespace Ilex\Base\Model\Feature;

use ReflectionClass;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseFeature
 * Base class of feature models of Ilex.
 * @package Ilex\Base\Model\Feature
 */
abstract class BaseFeature extends BaseModel
{

    final public function __call($method_name, $args)
    {
        if (FALSE === (new ReflectionClass(get_called_class()))->hasMethod($method_name))
            return FALSE;
        $handler_prefix = Loader::getHandlerPrefixFromPath(get_called_class(), '\\', ['']);

        // $config_model_name = $handler_prefix . 'Config';
        // self::loadModel("Config/$config_model_name");
        // $data_model_name   = $handler_prefix . 'Data';
        // self::loadModel("Data/$data_model_name");
        // 
        // $feature_config = call_user_func([
        //     self::$$config_model_name, 'getFeatureConfig'
        //     ], $method_name
        // );
        
        $feature_config = NULL;
        if (count($args) == 4) {
            list($input, $feature_config, $computation_data, $operation_status) = $args;
            $is_from_service_controller = FALSE;
        } else {
            list($input, $feature_config, $computation_data, $operation_status, $is_from_service_controller) = $args;
        }
        // http://php.net/manual/en/function.forward-static-call.php
        // forward_static_call(array(get_called_class(), $method_name), 'more', 'args');
        if (TRUE === $is_from_service_controller) {
            call_user_func([, $method_name], param_arr)
        } else {

        }
    }

}