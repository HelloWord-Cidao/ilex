<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Base\Controller\BaseController;
use \Ilex\Core\Http;
use \Ilex\Core\Loader;

/**
 * Class BaseService
 * Base class of service controllers.
 * @package Ilex\Base\Controller\Service
 *
 * @property private boolean $closeCgiOnly
 * @property private array   $jsonData
 * @property private int     $statusCode
 *
 * @method public __construct()
 * @method protected array fetchArguments(array[] $argument_names)
 * @method protected array fetchData(array[] $field_names)
 * @method protected       responseWithSuccess(mixed $data = NULL)
 * @method protected array tryFetchArguments(array[] $argument_names)
 * @method protected array tryFetchData(array[] $field_names)
 * @method protected       validateComputationData(mixed $data)
 * @method protected       validateExistArguments(array[] $argument_names)
 * @method protected       validateExistFields(array[] $field_names)
 * @method protected       validateOperationStatus(array|boolean $status)
 * 
 * @method private         output()
 * @method private         response(array $data, mixed $status)
 * @method private         terminateForFailedComputation(mixed $err_info = NULL)
 * @method private         terminateForFailedOperation(mixed $err_info = NULL)
 * @method private         terminateForMissingArguments(mixed $err_info = NULL)
 * @method private         terminateForMissingFields(mixed $err_info = NULL)
 * @method private         terminate(string $err_msg, mixed $err_info = NULL)
 */
class BaseService extends BaseController
{
    private $closeCgiOnly = FALSE; // 不exit, fast_cgi_close.让后面的脚本继续run
    private $jsonData   = [];
    private $statusCode = 200;

    public function __construct()
    {
        $this->loadModel('System/Input');
        $this->loadModel('System/Session');
        $this->loadModel('Core/Log');
    }

    public function __call($method_name, $args) 
    {
        if (in_array($method_name, get_class_methods($this)) === FALSE) return;
        $handler_prefix = Loader::getHandlerPrefixFromPath(get_called_class(), '\\', ['Service']);
        $arguments = []; $post_data = [];
        call_user_func_array([$this, $method_name], [&$arguments, &$post_data]);
        $computation_data = NULL; $operation_status = TRUE;
        call_user_func_array([$this->$handler_prefix, $method_name]
            , [$arguments, $post_data, &$computation_data, &$operation_status]);
        $this->validateComputationData($computation_data);
        $this->validateOperationStatus($operation_status);
        $this->Log->logRequest($operation_status, $arguments, $post_data);
        $this->responseWithSuccess($computation_data, $operation_status);
    }

    /**
     * @param mixed $computation_data
     *        mixed                      ok
     *        array [T_IS_ERROR => TRUE] error
     */
    protected function validateComputationData($computation_data)
    {
        if (is_array($computation_data) && $computation_data[T_IS_ERROR] === TRUE)
            $this->terminate('Computation failed.', $computation_data);
    }

    /**
     * @param array|boolean $operation_status
     *        boolean TRUE                  ok
     *        array   [T_IS_ERROR => FALSE] ok
     *        boolean FALSE                 error
     *        array   [T_IS_ERROR => TRUE]  error
     *        other   other                 error
     */
    protected function validateOperationStatus($operation_status)
    {
        if (!($operation_status === TRUE 
            || (is_array($operation_status) && $operation_status[T_IS_ERROR] === FALSE))) {
            $this->terminate('Operation failed.', $operation_status);
        }
    }

    /**
     * @param string $err_msg
     * @param mixed  $err_info
     */
    private function terminate($err_msg, $err_info = NULL)
    {
        $result = ['success' => FALSE, 'errMsg' => $err_msg];
        unset($err_info[T_IS_ERROR]);
        if (ENVIRONMENT === 'TEST' && !is_null($err_info))
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
    protected function responseWithSuccess($computation_data, $operation_status)
    {
        $result = ['success' => TRUE];
        unset($computation_data[T_IS_ERROR]);
        unset($operation_status[T_IS_ERROR]);
        // if (!is_null($computation_data))
            $result['data'] = $computation_data;
        if (!is_null($operation_status))
            $result['status'] = $operation_status;
        $this->response($result, 200);
    }

    /**
     * @param array $result
     * @param mixed $status_code
     */
    private function response($result, $status_code)
    {
        if (is_numeric($status_code) === FALSE) {
        // @todo: which case is not numeric?
            $this->statusCode = 400;
        } else {
            $this->statusCode = $status_code;
        }
        $this->jsonData = $result;
        $this->output();
    }

    private function output()
    {
        header('Content-Type : application/json', TRUE, $this->statusCode);
        Http::json($this->jsonData);
        if ($this->closeCgiOnly) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }
}