<?php

namespace Ilex\Base\Model\Feature;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use \Ilex\Lib\Loader;
use \Ilex\Lib\UserException;
use \Ilex\Lib\Kit;
use \Ilex\Base\Model\BaseModel;

/**
 * Class BaseFeature
 * Base class of feature models of Ilex.
 * @package Ilex\Base\Model\Feature
 */
abstract class BaseFeature extends BaseModel
{
    const V_PUBLIC     = 'V_PUBLIC';
    const V_PROTECTED  = 'V_PROTECTED';
    const V_PRIVATE    = 'V_PRIVATE';
    const T_SELF       = 'T_SELF';
    const T_DESCENDANT = 'T_DESCENDANT';
    const T_OTHER      = 'T_OTHER';

    final public function __call($method_name, $arg_list)
    {
        $execution_record     = self::prepareExecutionRecord($method_name, $arg_list);
        $class_name           = $execution_record['class'];
        $method_accessibility = $execution_record['method_accessibility'];
        if (FALSE === $method_accessibility) 
            throw new UserException('Method is not accessible.', $execution_record);
        $handler_prefix       = $execution_record['handler_prefix'];
        try {
            $config_model_name = $handler_prefix . 'Config';
            // Method validateFeaturePrivilege should throw exception if the validation fails.
            $execution_record['feature_privilege_validation_result']
                = $this->$config_model_name->validateFeaturePrivilege($method_name);

            $data_model_name = $handler_prefix . 'Data';
            // Method validateArgs should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $execution_record['args_validation_result']
                = $args_validation_result
                = $this->$data_model_name->validateArgs($method_name, $arg_list);
            // Now the validation passed.
            // Method sanitizeArgs should load the config model and fetch the config info itself.
            $execution_record['args_sanitization_result']
                = $args_sanitization_result // a list
                = $this->$data_model_name->sanitizeArgs(
                    $method_name, $arg_list, $args_validation_result);
            
            $execution_record['feature_behavior']
                = $feature_behavior
                = $this->$config_model_name->getServerFeatureBehavior($method_name);
            
            $execution_record['result']
                = $result
                = call_user_func_array(
                    [$this, $method_name], $args_sanitization_result + [ $feature_behavior ]);
            
            // Method validateResult should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $execution_record['result_validation_result']
                = $result_validation_result
                = $this->$data_model_name->validateResult($method_name, $result);
            // Now the validation passed.
            // Method sanitizeResult should load the config model and fetch the config info itself.
            $execution_record['result_sanitization_result']
                = $result_sanitization_result
                = $this->$data_model_name->sanitizeResult(
                    $method_name, $result, $result_validation_result);
        } catch (Exception $e) {
            throw new UserException('Feature execution failed.', $execution_record, $e);
        } finally {
            Kit::addToTraceStack($execution_record);
        }
        return $result;
    }

    final private function prepareExecutionRecord($method_name, $arg_list)
    {
        $class_name           = get_called_class();
        $class                = new ReflectionClass($class_name);
        if (FALSE === $class->hasMethod($method_name))
            throw new UserException("Method($method_name) does not exist in class($class_name).");
        $handler_prefix       = Loader::getHandlerPrefixFromPath(
            $class_name, '\\', ['Core', 'Collection', 'Log']);
        $method               = new ReflectionMethod($class_name, $method_name);
        $declaring_class      = $method->getDeclaringClass();
        $declaring_class_name = $declaring_class->getName();
        $methods_visibility   = $declaring_class->getConstant('METHODS_VISIBILITY');
        $method_visibility    = self::getMethodVisibility($methods_visibility, $method_name);
        $param_list           = Kit::recoverFunctionParameters($class_name, $method_name, $arg_list);
        list($initiator_class_name, $initiator_type)
            = self::getInitiatorNameAndType($method_name, $declaring_class);
        $method_accessibility = self::getMethodAccessibility($method_visibility, $initiator_type);

        $execution_record = [
            'class'                => $class_name,
            'method'               => $method_name,
            'param'                => $param_list,
            'method_accessibility' => $method_accessibility,
            'declaring_class'      => $declaring_class_name,
            'method_visibility'    => $method_visibility,
            'initiator_class'      => $initiator_class_name,
            'initiator_type'       => $initiator_type,
            'handler_prefix'       => $handler_prefix,
        ];
        return $execution_record;
    }

    final private function getMethodVisibility($methods_visibility, $method_name)
    {
        if (TRUE === isset($methods_visibility[self::V_PUBLIC])
            AND TRUE === isset($methods_visibility[self::V_PROTECTED])
            AND count(array_intersect(
                $methods_visibility[self::V_PUBLIC],
                $methods_visibility[self::V_PROTECTED])) > 0)
            throw new UserException('Public duplicates protected.');
        foreach ([self::V_PUBLIC, self::V_PROTECTED] as $type) {
            if (TRUE === isset($methods_visibility[$type])
                AND TRUE === in_array($method_name, $methods_visibility[$type])) {
                return $type;
            }
        }
        return self::V_PRIVATE;
    }

    final private function getInitiatorNameAndType($method_name, $declaring_class)
    {
        $backtrace          = debug_backtrace();
        $current_class_name = get_class();
        $initiator_name     = NULL;
        foreach ($backtrace as $record) {
            if (TRUE === is_null($record['class']) OR $current_class_name === $record['class'])
                continue;
            if ($method_name != $record['function']) {
                $initiator_name = $record['class'];
                break;
            }
        }
        if (TRUE === is_null($initiator_name))
            return [ $initiator_name, self::T_OTHER ];
        $initiator = new ReflectionClass($initiator_name);
        $declaring_class_name = $declaring_class->getName();
        if ($initiator_name === $declaring_class_name) {
            return [ $initiator_name, self::T_SELF ];
        } elseif (TRUE === $initiator->isSubclassOf($declaring_class_name)) {
            return [ $initiator_name, self::T_DESCENDANT ];
        } else return [ $initiator_name, self::T_OTHER ];
    }

    final private function getMethodAccessibility($method_visibility, $initiator_type)
    {
        if (self::V_PUBLIC === $method_visibility) {
            return TRUE;
        } elseif (self::V_PROTECTED === $method_visibility) {
            if (self::T_OTHER === $initiator_type) {
                return FALSE;
            } else return TRUE;

        } elseif (self::V_PRIVATE === $method_visibility) {
            if (self::T_SELF === $initiator_type) {
                return TRUE;
            } else return FALSE;
        }
        throw new UserException('Method accessibility calculation failed.');
    }

}