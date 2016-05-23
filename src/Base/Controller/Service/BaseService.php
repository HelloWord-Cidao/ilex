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
 * @property private array   $jsonData
 * @property private int     $statusCode
 *
 * @method final public __construct()
 * @method final public __call(string $method_name, array $args)
 * 
 * @method final private fail(string $err_msg, mixed $err_info = NULL)
 * @method final private output()
 * @method final private response(array $data, mixed $status)
 * @method final private success(mixed $data = NULL)
 * @method final private validateComputationData(mixed $data)
 * @method final private validateOperationStatus(array|boolean $status)
 */
abstract class BaseService extends BaseController
{
    private $closeCgiOnly = FALSE; // 不exit, fast_cgi_close.让后面的脚本继续run
    private $jsonData     = [];
    private $statusCode   = 200;

    final public function __construct()
    {
        self::loadModel('System/Input');
        self::loadModel('System/Session');
        self::loadModel('Feature/Log/RequestLog');
    }

    final public function __call($method_name, $args) 
    {
        $is_time_consuming = $args[0];
        $handler_prefix = Loader::getHandlerPrefixFromPath(get_called_class(), '\\', ['Service']);
        $input = self::$Input->input();
        $data_model_name = $handler_prefix . 'Data';
        $core_model_name = $handler_prefix . 'Core';
        self::loadModel("Data/$data_model_name");

        $validation_result = call_user_func([
            self::$$data_model_name, 'validateInput'
            ], $method_name, $input
        );
        exit();
        if (TRUE === $is_time_consuming) {

        }
        $computation_data = NULL; $operation_status = TRUE;
        call_user_func_array([
            self::$$core_model_name, $method_name
            ], [$arguments, $post_data, &$computation_data, &$operation_status]);
        $this->validateComputationData($computation_data);
        $this->validateOperationStatus($operation_status);
        self::$RequestLog->addRequestLog($input, $operation_status);
        $this->success($computation_data, $operation_status);
    }

    public function setCloseCgiOnly()
    {
        $this->_close_cgi_only = TRUE;
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
    final private function success($computation_data, $operation_status)
    {
        $result = ['success' => TRUE];
        unset($computation_data[T_IS_ERROR]);
        unset($operation_status[T_IS_ERROR]);
        // if (FALSE === is_null($computation_data))
            $result['data'] = $computation_data;
        if (FALSE === is_null($operation_status))
            $result['status'] = $operation_status;
        $this->response($result, 200);
    }

    /**
     * @param array $result
     * @param mixed $status_code
     */
    final private function response($result, $status_code)
    {
        if (FALSE === is_numeric($status_code)) {
        // @todo: which case is not numeric?
            $this->statusCode = 400;
        } else {
            $this->statusCode = $status_code;
        }
        $this->jsonData = $result;
        $this->output();
    }

    final private function output()
    {
        header('Content-Type : application/json', TRUE, $this->statusCode);
        Http::json($this->jsonData);
        if (TRUE === $this->closeCgiOnly) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else exit();
    }
}