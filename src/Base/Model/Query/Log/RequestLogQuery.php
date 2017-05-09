<?php

namespace Ilex\Base\Model\Query\Log;

use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Query\BaseQuery;

/**
 * Class RequestLogQuery
 * @package Ilex\Base\Model\Query\Log
 */
final class RequestLogQuery extends BaseQuery
{
    final public function requestURIIs($URI)
    {
        return $this->dataFieldIs('Request.RequestURI', $URI);
    }
}