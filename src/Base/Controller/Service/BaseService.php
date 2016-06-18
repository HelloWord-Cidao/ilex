<?php

namespace Ilex\Base\Controller\Service;

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
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

    private $result   = [
        'code'   => NULL,
        'data'   => [ ],
        'status' => [ ],
    ];
    private $isFinish = FALSE;

    /**
     * @param string $method_name
     * @param array  $arg_list
     */
    final public function __call($method_name, $arg_list) 
    {
        $execution_record = [];
        $execution_id = Debug::addExecutionRecord($execution_record);
        Debug::pushExecutionId($execution_id);
        try {
            $execution_record     = $this->prepareExecutionRecord($method_name, $arg_list);
            $class_name           = $execution_record['class'];
            $input                = $execution_record['input'];
            $method_accessibility = $execution_record['method_accessibility'];
            $handler_prefix       = $execution_record['handler_prefix'];
            $handler_suffix       = $execution_record['handler_suffix'];
            Debug::updateExecutionRecord($execution_id, $execution_record);

            if (FALSE === $method_accessibility) 
                throw new UserException(
                    "Handler($class_name :: $method_name) is not accessible.", $execution_record);

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
            if (TRUE === $is_time_consuming) $this->succeedRequest(NULL, NULL, TRUE);

            // Method sanitizeInput should load the config model and fetch the config info itself.
            $input_sanitization_result // a list
                = $execution_record['sanitizeInput']
                = $this->$data_model_name->sanitizeInput(
                    $method_name, $input, $input_validation_result);
            
            $this->$method_name($input_sanitization_result);
            $service_result
                = $execution_record['service_result']
                = $this->result;
            
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
            // and only contains three fields: code, data, status.
            $code             = $service_result_sanitization_result['code'];
            $computation_data = $service_result_sanitization_result['data'];
            $operation_status = $service_result_sanitization_result['status'];
            
            $this->loadModel('Feature/Log/RequestLog');
            $this->RequestLog->addRequestLog(
                $execution_record['class'],
                $execution_record['method'],
                $input,
                $code,
                $operation_status
            );
            $this->succeedRequest($execution_id, $execution_record);
        } catch (Exception $e) {
            $this->failRequest(
                $execution_id, $execution_record,
                new UserException(
                    'Service execution failed.', $execution_record, $e
                )
            );
        }
    }

    final protected function call($method_name) {
        // @TODO: implement this method
        $arg_list = func_get_args();
        $method_name = $arg_list[0];
        $arg_list = array_slice($arg_list, 1);
        return call_user_func_array([$this, $method_name], $arg_list);
    }

    /**
     * @param string $method_name
     * @return array
     */
    final private function prepareExecutionRecord($method_name)
    {
        $this->loadModel('System/Input');
        $input      = $this->Input->input();
        $class_name = get_called_class();
        
        $execution_record = $this->generateExecutionRecord($class_name, $method_name);
        $execution_record += [
            'input' => $input,
        ];
        return $execution_record;
    }

    final protected function succeed()
    {
        $this->setCode(2);
    }

    final protected function fail()
    {
        $this->setCode(1);
    }

    final private function setCode($code)
    {
        if (FALSE === in_array($code, [ 0, 1, 2, 3 ]))
            throw new UserException('Invalid $code.', $code);
        if (FALSE === is_null($this->result['code']))
            throw new UserException("code is not NULL before set to $code");
        if (TRUE === $this->checkFinish()) {
            if (0 !== $code) {
                $msg = "Can not change code to $code after service has finished.";
                throw new UserException($msg);
            }
        }
        $this->finish();
        $this->result['code'] = $code;
        return $code;
    }

    final private function getCode()
    {
        return $this->result['code'];
    }

    final protected function data($name = NULL, $value = '@[EMPTY]#')
    {
        return $this->handleResult('computation_data', $name, $value);
    }

    final protected function status($name = NULL, $value = '@[EMPTY]#')
    {
        return $this->handleResult('operation_status', $name, $value);
    }

    final private function handleResult($type, $name = NULL, $value = '@[EMPTY]#')
    {
        if (TRUE === $this->checkFinish())
            throw new UserException('Can not handle result after service has finished.');
        if (FALSE === in_array($type, [ 'computation_data', 'operation_status' ]))
            throw new UserException('Invalid $type.', $type);
        if (TRUE === is_string($name)) {
            if ('@[EMPTY]#' === $value) // (valid)
                return $this->getResult($type, $name);
            // (valid, valid/NULL)
            return $this->setResult($type, $name, $value);
        } elseif (TRUE === is_null($name)) {
            if ('@[EMPTY]#' === $value) // (NULL) / ()
                return $this->getResult($type);
            // (NULL, valid/NULL)
            throw new UserException('Invalid $value(NULL) when $name is NULL.');
        } else {
            // (invalid, valid/NULL/empty)
            throw new UserException('Invalid $name.', $name);
        }
    }

    final private function setResult($type, $name, $value)
    {
        if (FALSE === in_array($type, [ 'computation_data', 'operation_status' ]))
            throw new UserException('Invalid $type.', $type);
        $type = 'computation_data' === $type ? 'data' : 'status';
        if (FALSE === is_string($name))
            throw new UserException('$name is not a string.', $name);
        if ('' === $name)
            throw new UserException('$name is an empty string.', $type);
        $this->result[$type][$name] = $value;
        return $value;
    }

    final private function getResult($type, $name = NULL)
    {
        if (FALSE === in_array($type, [ 'computation_data', 'operation_status' ]))
            throw new UserException('Invalid $type.', $type);
        $type = 'computation_data' === $type ? 'data' : 'status';
        if (TRUE === is_null($name))
            return $this->result[$type];
        if (FALSE === is_string($name))
            throw new UserException('$name is not a string.', $name);
        if (FALSE === isset($this->result[$type][$name])) {
            $msg = "Field($name) does not exist in $type.";
            throw new UserException($msg, $this->result[$type]);
        }
        return $this->result[$type][$name];
    }

    final private function finish()
    {
        $this->isFinish = TRUE;
    }

    final private function checkFinish()
    {
        return $this->isFinish;
    }

    /**
     * @param boolean $close_cgi_only
     */
    final private function succeedRequest($execution_id, $execution_record
        , $close_cgi_only = FALSE)
    {
        $code = $this->getCode();
        if (TRUE === is_null($code)) {
            $this->setCode(3);
        } elseif (FALSE === in_array($code, [ 1, 2 ])) {
            throw new UserException('Invalid code.', $code);
        }
        // Now code must be 1 or 2 or 3.
        $this->response($execution_id, $execution_record, 200, $close_cgi_only);
    }

    /**
     * @param Exception $exception
     */
    final private function failRequest($execution_id, $execution_record, $exception)
    {
        $code = $this->getCode();
        if (FALSE === is_null($code) AND FALSE === in_array($code, [ 1, 2 ])) {
            throw new UserException('Invalid code.', $code);
        }
        // Now code must be NULL or 1 or 2.
        $this->setCode(0);
        if (FALSE === Debug::isProduction())
            $this->result['exception'] = Debug::extractException($exception);
        $this->response($execution_id, $execution_record, 200); // @TODO: change code
    }

    /**
     * @param int     $status_code
     * @param boolean $close_cgi_only
     */
    final private function response($execution_id, $execution_record
        , $status_code, $close_cgi_only = FALSE)
    {
        if (FALSE === $close_cgi_only) {
            Debug::updateExecutionRecord($execution_id, $execution_record);
            Debug::popExecutionId($execution_id);
        }
        header('Content-Type : application/json', TRUE, $status_code);
        if (FALSE === Debug::isProduction())
            $this->result['trace'] = Debug::getExecutionRecordStack();
        Http::json($this->result);
        if (TRUE === $close_cgi_only) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }
}