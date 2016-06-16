<?php

namespace Ilex\Base\Controller\Service;

use \Exception;
use \Ilex\Core\Debug;
use \Ilex\Core\Loader;
use \Ilex\Lib\Http;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;
use \Ilex\Base\Controller\BaseController;

/**
 * Class BaseService
 * Base class of service controllers.
 * @package Ilex\Base\Controller\Service
 *
 * @method final public __construct()
 * @method final public __call(string $method_name, array $arg_list)
 * 
 * @method final private       fail(Exception $exception)
 * @method final private array prepareExecutionRecord(string $method_name)
 * @method final private       response(array $result, int $status_code
 *                                 , boolean $close_cgi_only = FALSE)
 * @method final private       succeed(mixed $computation_data, mixed $operation_status
 *                                 , boolean $close_cgi_only = FALSE)
 */
abstract class BaseService extends BaseController
{

    /**
     * @param string $method_name
     * @param array  $arg_list
     */
    final public function __call($method_name, $arg_list) 
    {
        try {
            $execution_record = self::prepareExecutionRecord($method_name, $arg_list);
            $class_name       = $execution_record['class'];
            $input            = $execution_record['input'];
            $handler_prefix   = $execution_record['handler_prefix'];
            $handler_suffix   = $execution_record['handler_suffix'];
            $execution_id     = Debug::addExecutionRecord($execution_record);
            Debug::pushExecutionId($execution_id);
            $config_model_name = $handler_prefix . 'Config';
            if (TRUE === is_null($this->$config_model_name))
                throw new UserException("Config model($config_model_name) not loaded in $class_name.");
            // Method validateFeaturePrivilege should throw exception if the validation fails.
            $execution_record['validateFeaturePrivilege']
                = $this->$config_model_name->validateFeaturePrivilege($handler_suffix, $method_name);

            $data_model_name = $handler_prefix . 'Data';
            if (TRUE === is_null($this->$data_model_name))
                throw new UserException("Data model($data_model_name) not loaded in $class_name.");
            // Method validateInput should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $input_validation_result
                = $execution_record['validateInput']
                = $this->$data_model_name->validateInput($method_name, $input);
            // Now the validation passed.
            
            $execution_record['is_time_consuming'] = $is_time_consuming = $arg_list[0];
            if (TRUE === $is_time_consuming)
                $this->succeed(NULL, 'Request data received successfully, operation has started.', TRUE);

            // Method sanitizeInput should load the config model and fetch the config info itself.
            $input_sanitization_result // a list
                = $execution_record['sanitizeInput']
                = $this->$data_model_name->sanitizeInput(
                    $method_name, $input, $input_validation_result);
            
            $core_model_name = $handler_prefix . 'Core';
            if (TRUE === is_null($this->$core_model_name))
                throw new UserException("Core model($core_model_name) not loaded in $class_name.");
            $service_result
                = $execution_record['service_result']
                = $this->$core_model_name->$method_name($input_sanitization_result);
            
            // Method validateServiceResult should throw exception if the validation fails,
            // and it should load the config model and fetch the config info itself.
            $service_result_validation_result
                = $execution_record['validateServiceResult']
                = $this->$data_model_name->validateServiceResult($method_name, $service_result);
            // Now the validation passed.
            
            // Method sanitizeServiceResult should load the config model
            // and fetch the config info itself.
            $service_result_sanitization_result
                = $execution_record['sanitizeServiceResult']
                = $this->$data_model_name->sanitizeServiceResult(
                    $method_name, $service_result, $service_result_validation_result);
            // $service_result_validation_result should contains
            // and only contains two fields: data, status.
            $computation_data = $service_result_sanitization_result['data'];
            $operation_status = $service_result_sanitization_result['status'];
            
            $this->loadModel('Feature/Log/RequestLog');
            $this->RequestLog->addRequestLog(
                $execution_record['class'],
                $execution_record['method'],
                $input,
                $operation_status
            );
            $execution_record['success'] = TRUE;
            Debug::updateExecutionRecord($execution_id, $execution_record);
            Debug::popExecutionId($execution_id);
            $this->succeed($computation_data, $operation_status);
        } catch (Exception $e) {
            Debug::updateExecutionRecord($execution_id, $execution_record);
            Debug::popExecutionId($execution_id);
            $this->fail(new UserException('Service execution failed.', $execution_record, $e));
        }
    }

    /**
     * @param string $method_name
     * @return array
     */
    final private function prepareExecutionRecord($method_name)
    {
        $this->loadModel('System/Input');
        $class_name     = get_called_class();
        $handler_prefix = Loader::getHandlerPrefixFromPath($class_name, ['Service']);
        $handler_suffix = Loader::getHandlerSuffixFromPath($class_name, ['Service']);
        $input          = $this->Input->input();
        $execution_record = [
            'success'        => FALSE,
            'class'          => $class_name,
            'method'         => $method_name,
            'input'          => $input,
            'handler_prefix' => $handler_prefix,
            'handler_suffix' => $handler_suffix,
        ];
        return $execution_record;
    }

    /**
     * @param Exception $exception
     */
    final private function fail($exception)
    {
        $result = [ 'success' => FALSE ];
        if (FALSE === Debug::isProduction())
            $result['exception'] = Debug::extractException($exception);
        $this->response($result, 200);
    }

    /**
     * @param mixed   $computation_data
     * @param mixed   $operation_status
     * @param boolean $close_cgi_only
     */
    final private function succeed($computation_data, $operation_status, $close_cgi_only = FALSE)
    {
        $result = [
            'success' => TRUE,
            'data'    => $computation_data,
            'status'  => $operation_status,
        ];
        $this->response($result, 200, $close_cgi_only);
    }

    /**
     * @param array   $result
     * @param int     $status_code
     * @param boolean $close_cgi_only
     */
    final private function response($result, $status_code, $close_cgi_only = FALSE)
    {
        header('Content-Type : application/json', TRUE, $status_code);
        if (FALSE === Debug::isProduction())
            $result['trace'] = Debug::getExecutionRecordStack();
        Http::json($result);
        if (TRUE === $close_cgi_only) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }
}