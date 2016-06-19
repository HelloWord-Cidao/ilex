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

    const R_EMPTY = '@[EMPTY]#';

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

            $config_model_name = $this->configModelName;
            if (TRUE === is_null($config_model_name) OR TRUE === is_null($this->$config_model_name))
                throw new UserException("Config model($config_model_name) not loaded in $class_name.");
            // Method validateModelPrivilege should throw exception if the validation fails.
            $execution_record['validateModelPrivilege']
                = $this->$config_model_name->validateModelPrivilege($handler_suffix, $method_name);

            $data_model_name = $this->dataModelName;
            if (TRUE === is_null($data_model_name) OR TRUE === is_null($this->$data_model_name))
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
            
            $this->loadCore('Log/Request');
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
        $arg_list = Kit::sliceList($arg_list, 1);
        return call_user_func_array([$this, $method_name], $arg_list);
    }

    /**
     * @param string $method_name
     * @return array
     */
    final private function prepareExecutionRecord($method_name)
    {
        $this->loadInput();
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
        $current_code = $this->result['code'];
        if (FALSE === Kit::inList($code, [ 0, 1, 2, 3 ]))
            throw new UserException('Invalid $code.', $code);
        if (FALSE === is_null($current_code) 
            AND FALSE === Kit::inList($current_code, [ 0, 1, 2, 3 ]))
            throw new UserException('Invalid $current_code.', $current_code);
        if (TRUE === Kit::inList($current_code, [ 0, 3 ]))
            throw new UserException("Can not change code(${current_code}) to $code after the request has finished.", $current_code);
            
        if (TRUE === $this->checkFinish()) {
            if (0 !== $code) {
                $msg = "Can not change code(${current_code}) to $code after service has finished.";
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

    final protected function data($name = NULL, $value = self::R_EMPTY, $is_list = FALSE)
    {
        return $this->handleResult('computation_data', $name, $value, $is_list);
    }

    final protected function status($name = NULL, $value = self::R_EMPTY, $is_list = FALSE)
    {
        return $this->handleResult('operation_status', $name, $value, $is_list);
    }

    final private function handleResult($type, $name, $value, $is_list)
    {
        if (TRUE === $this->checkFinish())
            throw new UserException('Can not handle result after service has finished.');
        if (FALSE === Kit::inList($type, [ 'computation_data', 'operation_status' ]))
            throw new UserException('Invalid $type.', $type);
        if (TRUE === is_string($name)) {
            if (self::R_EMPTY === $value) // (valid)
                return $this->getResult($type, $name);
            // (valid, valid/NULL)
            return $this->setResult($type, $name, $value, $is_list);
        } elseif (TRUE === is_null($name)) {
            if (self::R_EMPTY === $value) // (NULL) / ()
                return $this->getResult($type, NULL);
            // (NULL, valid/NULL)
            throw new UserException('Invalid $value when $name is NULL.', $value);
        } else {
            // (invalid, valid/NULL/empty)
            throw new UserException('Invalid $name.', $name);
        }
    }

    final private function setResult($type, $name, $value, $is_list)
    {
        if (FALSE === Kit::inList($type, [ 'computation_data', 'operation_status' ]))
            throw new UserException('Invalid $type.', $type);
        $type = 'computation_data' === $type ? 'data' : 'status';
        if (FALSE === is_string($name))
            throw new UserException('$name is not a string.', $name);
        if ('' === $name)
            throw new UserException('$name is an empty string.', $type);
        if (TRUE === isset($this->result[$type][$name])) {
            if (TRUE === $is_list) {
                if (TRUE === Kit::isList($this->result[$type][$name])) {
                    $this->result[$type][$name][] = $value;
                } else {
                    $msg = "\$this->result[$type][$name] is a non-empty non-list variable.";
                    throw new UserException($msg, $this->result[$type][$name]);
                }
            } else $this->result[$type][$name] = $value;
        } else {
            if (TRUE === $is_list)
                $this->result[$type][$name] = [ $value ];
            else $this->result[$type][$name] = $value;
        }
        return $value;
    }

    final private function getResult($type, $name)
    {
        if (FALSE === Kit::inList($type, [ 'computation_data', 'operation_status' ]))
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
    final private function succeedRequest($execution_id, $execution_record , $close_cgi_only = FALSE)
    {
        $code = $this->getCode();
        if (TRUE === is_null($code)) {
            if (TRUE === $close_cgi_only) { // NULL 1 => 3
                $this->setCode(3);
            } else { // NULL 0 => error
                $msg = 'Can not succeed the request before service is finished and code is NULL.';
                throw new UserException($msg);
            }
        } elseif (FALSE === Kit::inList($code, [ 1, 2 ])) { // 0/3 0/1 => error
            throw new UserException('Invalid code.', $code);
        } elseif (TRUE === $close_cgi_only) { // 1/2 1 => error
            throw new UserException('Can not only close cgi after service has finished.', $code);
        } else { // 1/2 0 => 1/2
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
        if (FALSE === is_null($code) AND FALSE === Kit::inList($code, [ 1, 2 ])) {
            throw new UserException('Can not fail the request because of invalid code.', $code);
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
        if (FALSE === Debug::isProduction()) {
            $this->result += [
                'trace'  => Debug::getExecutionRecordStack(),
                'memory' => Debug::getMemoryUsed(),
                'time'   => Debug::getTimeUsed(),
            ];
        }
        Http::json($this->result);
        if (TRUE === $close_cgi_only) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }
}