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