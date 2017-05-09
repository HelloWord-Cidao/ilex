<?php

namespace Ilex\Base\Model;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Base;

/**
 * Class BaseModel
 * Base class of models.
 * @package Ilex\Base\Model
 */
abstract class BaseModel extends Base
{
    final public function __call($method_name, $arg_list)
    {
        return call_user_func_array([$this, 'call'], array_merge([ $method_name ], $arg_list));
    }
    
    final protected function call($method_name)
    {
        $arg_list = func_get_args();
        $method_name = $arg_list[0];
        $arg_list = Kit::slice($arg_list, 1);
        return $this->execute($method_name, $arg_list);
    }

    final protected function callParent($method_name)
    {
        $arg_list = func_get_args();
        $method_name = $arg_list[0];
        $arg_list = Kit::slice($arg_list, 1);
        return $this->execute($method_name, $arg_list, TRUE);
    }

    final private function execute($method_name, $arg_list, $call_parent = FALSE)
    {
        $execution_record = [];
        $execution_id = Debug::addExecutionRecord($execution_record);
        Debug::pushExecutionId($execution_id);
        try {
            $execution_record     = $this->prepareExecutionRecord($method_name, $arg_list, $call_parent);
            $class_name           = $execution_record['class'];
            $declaring_class_name = $execution_record['declaring_class'];
            $method_accessibility = $execution_record['method_accessibility'];
            $handler_prefix       = $execution_record['handler_prefix'];
            $handler_suffix       = $execution_record['handler_suffix'];
            Debug::updateExecutionRecord($execution_id, $execution_record);
            
            if (($count = Debug::countExecutionRecord()) > 3000000)
                throw new UserException('Abnormal execution record count.', $count);
            if (FALSE === $method_accessibility) 
                throw new UserException(
                    "Handler($declaring_class_name :: $method_name) is not accessible.", $execution_record);

            $config_model_name = $this->loadConfig(Loader::getModelPath($declaring_class_name));
                
            // Method validateModelPrivilege should throw exception if the validation fails.
            $execution_record['validateModelPrivilege']
                = $this->$config_model_name->validateModelPrivilege($handler_suffix, $method_name);

            $data_model_name = $this->loadData(Loader::getModelPath($declaring_class_name));

            // Method validateArgs should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $args_validation_result
                = $execution_record['validateArgs']
                = $this->$data_model_name->validateArgs($handler_suffix, $method_name, $arg_list);
            // Now the validation passed.
            // Method sanitizeArgs should load the config model and fetch the config info itself.
            $args_sanitization_result // a list
                = $execution_record['sanitizeArgs']
                = $this->$data_model_name->sanitizeArgs(
                    $handler_suffix, $method_name, $arg_list, $args_validation_result);

            $result
                = $execution_record['result']
                = call_user_func_array(
                    [ $declaring_class_name, $method_name ], $args_sanitization_result);
            
            // Method validateResult should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $result_validation_result
                = $execution_record['validateResult']
                = $this->$data_model_name->validateResult($handler_suffix, $method_name, $result);
            // Now the validation passed.
            // Method sanitizeResult should load the config model and fetch the config info itself.
            $result_sanitization_result
                = $execution_record['sanitizeResult']
                = $this->$data_model_name->sanitizeResult(
                    $handler_suffix, $method_name, $result, $result_validation_result);
            $execution_record['success'] = TRUE;
            Debug::updateExecutionRecord($execution_id, $execution_record);
            Debug::popExecutionId($execution_id);
            return $result;
        } catch (Exception $e) {
            $execution_record['success'] = FALSE;
            Debug::updateExecutionRecord($execution_id, $execution_record);
            Debug::popExecutionId($execution_id);
            throw new UserException('Model execution failed.', $execution_record, $e);
        }
    }

    final private function prepareExecutionRecord($method_name, $arg_list, $call_parent)
    {        
        if (TRUE === $call_parent) {
            // @TODO: check it!
            // $backtrace          = Kit::columns(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            //     [ 'class' ], FALSE);
            $current_class_name = get_class();
            foreach ($backtrace as $record) {
                if (TRUE === is_null($record['class']) OR $current_class_name === $record['class'])
                    continue;
                $class_name = $record['class'];
                break;
            }
            $parent_class = (new ReflectionClass($class_name))->getParentClass();
            try {
                $class_name = $parent_class->getName();
            } catch (Exception $e) {
                throw new UserException('Search parent failed.', $method_name, $e);
            }
        } else $class_name = get_called_class();

        $execution_record = $this->generateExecutionRecord($class_name, $method_name);
        $execution_record += [
            'args' => $arg_list,
        ];
        return $execution_record;
    }
}