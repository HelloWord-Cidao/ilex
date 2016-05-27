<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Core\Http;
use \Ilex\Core\Loader;
use \Ilex\Base\Controller\BaseController;

/**
 * Class BaseService
 * Base class of service controllers.
 * @package Ilex\Base\Controller\Service
 *
 * @property private boolean $closeCgiOnly
 * @property private int     $statusCode
 *
 * @method final public __construct()
 * @method final public __call(string $method_name, array $args)
 * 
 * @method final private fail(string $err_msg, mixed $err_info = NULL)
 * @method final private response(array $data, mixed $status)
 * @method final private setCloseCgiOnly()
 * @method final private succeed(mixed $data = NULL)
 * @method final private validateComputationData(mixed $data)
 * @method final private validateOperationStatus(array|boolean $status)
 */
abstract class BaseService extends BaseController
{
    private $closeCgiOnly = FALSE; // 不exit, fast_cgi_close.让后面的脚本继续run

    final public function __construct()
    {
        $this->loadModel('System/Input');
        $this->loadModel('System/Session');
        $this->loadModel('Feature/Log/RequestLog');
    }

    final public function __call($method_name, $args) 
    {
        $handler_prefix = Loader::getHandlerPrefixFromPath(get_called_class(), '\\', ['Service']);
        
        $config_model_name = $handler_prefix . 'Config';
        $data_model_name   = $handler_prefix . 'Data';
        $core_model_name   = $handler_prefix . 'Core';

        // $input_config = call_user_func([
        //     $this->$config_model_name, 'getInputConfig'
        //     ], $method_name
        // );
        $input_config = NULL;
        $input = $this->$Input->input();
        // $validation_result = call_user_func([
        //     $this->$data_model_name, 'validateInput'
        //     ], $method_name, $input, $input_config
        // );
        $validation_result = TRUE;
        if (TRUE === $validation_result) { // @todo: more complicated structure of result
            // $input = call_user_func([
            //     $this->$data_model_name, 'sanitizeInput'
            //     ], $method_name, $input, $input_config, $validation_result
            // );
        } else $this->fail('Input validation failed.', $validation_result);

        // @todo: validate feature priviledge, via priviledge model?
        // @todo: validate data priviledge, via priviledge model?
        
        $is_time_consuming = $args[0];
        if (TRUE === $is_time_consuming) {
            $this->setCloseCgiOnly();
            $this->succeed(NULL, 'Request data received successfully, operation has started.')
        }

        $computation_data = NULL; $operation_status = TRUE;
        $execution_result = call_user_func_array([
            $this->$core_model_name, $method_name
            ], [$input, $feature_config, &$computation_data, &$operation_status, TRUE]
        );
        $this->validateExecutionResult($execution_result);
        $this->validateComputationData($computation_data);
        $this->validateOperationStatus($operation_status);
        $this->$RequestLog->addRequestLog($input, $operation_status);
        $this->succeed($computation_data, $operation_status);
    }

    final private function setCloseCgiOnly()
    {
        $this->closeCgiOnly = TRUE;
    }

    /**
     * @param array|boolean $execution_result
     *        boolean TRUE                  ok
     *        array   [T_IS_ERROR => FALSE] ok
     *        boolean FALSE                 error
     *        array   [T_IS_ERROR => TRUE]  error
     *        other   other                 error
     */
    final private function validateExecutionResult($execution_result)
    {
        if (FALSE === 
            (TRUE === $execution_result OR 
                (TRUE === is_array($execution_result) AND FALSE === $execution_result[T_IS_ERROR])
            )
        ) {
            $this->fail('Execution failed.', $execution_result);
        }
    }

    /**
     * @param mixed $computation_data
     *        mixed                      ok
     *        array [T_IS_ERROR => TRUE] error
     */
    final private function validateComputationData($computation_data)
    {
        if (TRUE === is_array($computation_data) AND TRUE === $computation_data[T_IS_ERROR])
            $this->fail('Computation failed.', $computation_data);
    }

    /**
     * @param array|boolean $operation_status
     *        boolean TRUE                  ok
     *        array   [T_IS_ERROR => FALSE] ok
     *        boolean FALSE                 error
     *        array   [T_IS_ERROR => TRUE]  error
     *        other   other                 error
     */
    final private function validateOperationStatus($operation_status)
    {
        if (FALSE === 
            (TRUE === $operation_status OR 
                (TRUE === is_array($operation_status) AND FALSE === $operation_status[T_IS_ERROR])
            )
        ) {
            $this->fail('Operation failed.', $operation_status);
        }
    }

    /**
     * @param string $err_msg
     * @param mixed  $err_info
     */
    final private function fail($err_msg, $err_info = NULL)
    {
        $result = ['success' => FALSE, 'errMsg' => $err_msg];
        unset($err_info[T_IS_ERROR]);
        if ('TEST' === ENVIRONMENT AND FALSE === is_null($err_info))
            $result['errInfo'] = $err_info;
        $this->response($result, 200);
    }

    /**
     * @param mixed $computation_data
     *        mixed                         ok
     * @param mixed $operation_status
     *        boolean TRUE                  ok
     *        array   [T_IS_ERROR => FALSE] ok
     *        NULL                          no $operation_status
     */
    final private function succeed($computation_data, $operation_status)
    {
        $result = ['success' => TRUE];
        unset($computation_data[T_IS_ERROR]);
        unset($operation_status[T_IS_ERROR]);
        // if (FALSE === is_null($computation_data))
            $result['data'] = $computation_data;
        // if (FALSE === is_null($operation_status))
            $result['status'] = $operation_status;
        $this->response($result, 200);
    }

    /**
     * @param array $result
     * @param mixed $status_code
     */
    final private function response($result, $status_code)
    {
        header('Content-Type : application/json', TRUE, $status_code);
        Http::json($result);
        if (TRUE === $this->closeCgiOnly) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }

}