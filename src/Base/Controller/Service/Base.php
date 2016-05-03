<?php

namespace Ilex\Base\Controller\Service;

use \Ilex\Core\Http;

/**
 * Class Base
 * Base class of service controllers.
 * @package Ilex\Base\Controller\Service
 *
 * @property private boolean $closeCgiOnly
 * @property private array   $jsonData
 * @property private int     $statusCode
 * 
 * @method protected this    closeCgi()
 * @method protected         response(array $data, mixed $status)
 * 
 * @method private output()
 */
class Base extends \Ilex\Base\Controller\Base
{
    private $closeCgiOnly = FALSE; // 不exit, fast_cgi_close.让后面的脚本继续run
    private $jsonData   = [];
    private $statusCode = 200;

    protected function fetchArguments($argument_names)
    {
        $this->validateExistArguments($argument_names);
        $arguments = [];
        foreach ($argument_names as $argument_name)
            $arguments[$argument_name] = $this->Input->get($argument_name);
        return $arguments;
    }

    protected function fetchData($field_names)
    {
        $this->validateExistFields($field_names);
        $data = [];
        foreach ($field_names as $field_name)
            $data[$field_name] = $this->Input->post($field_name);
        return $data;
    }

    private function validateExistArguments($argument_names)
    {
        if (!$this->Input->hasGet($argument_names)) {
            $err_info = $this->Input->missGet($argument_names);
            $this->terminateForMissingArguments($err_info);
        }
    }

    private function validateExistFields($field_names)
    {
        if (!$this->Input->hasPost($field_names)) {
            $err_info = $this->Input->missPost($argument_names);
            $this->terminateForMissingFields($err_info);
        }
    }

    protected function validateComputationData($data)
    {
        if (is_null($data))
            $this->terminateForFailedComputation('Null data.');
        if ($data === FALSE)
            $this->terminateForFailedComputation('False data.');
    }

    protected function validateOperationStatus($status)
    {
        // @todo: check how many kind of mongo collection operation status there are?
        if ($status === FALSE || is_array($status))
            $this->terminateForFailedOperation($status);
    }

    protected function responseWithSuccess($data = NULL)
    {
        $result = ['update' => TRUE];
        if (!is_null($data))
            $result['data'] = $data;
        $this->response($result, 200);
    }

    private function terminateForMissingArguments($err_info = NULL)
    {
        $this->terminate('Missing arguments.', $err_info);
    }

    private function terminateForMissingFields($err_info = NULL)
    {
        $this->terminate('Missing fields.', $err_info);
    }

    private function terminateForFailedComputation($err_info = NULL)
    {
        $this->terminate('Computation failed.', $err_info);
    }

    private function terminateForFailedOperation($err_info = NULL)
    {
        $this->terminate('Operation failed.', $err_info);
    }

    private function terminate($err_msg, $err_info = NULL)
    {
        $result = ['update' => FALSE, 'err_msg' => $err_msg];
        if (ENVIRONMENT === 'TEST' && !is_null($err_info))
            $result['err_info'] = $err_info;
        $this->response($result, 200);
    }

    /**
     * @todo what?
     * @return this
     */
    protected function closeCgi()
    {
        $this->closeCgiOnly = TRUE;
        return $this;
    }

    /**
     * @param array $data
     * @param mixed $status
     */
    protected function response($data, $status)
    {
        if (is_numeric($status) === FALSE) {
        // @todo: which case is not numeric?
            $this->statusCode = 400;
        } else {
            $this->statusCode = $status;
        }
        $this->jsonData = $data;
        $this->output();
    }
    
    private function output()
    {
        header('Content-Type : application/json', TRUE, $this->statusCode);
        Http::json($this->jsonData);
        if ($this->closeCgiOnly) {
            fastcgi_finish_request();
            // DO NOT exit in order to run the subsequent scripts.
        } else {
            exit();
        }
    }
}