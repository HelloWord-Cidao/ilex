<?php

namespace Ilex\Base\Model\Core;

use \Ilex\Lib\Kit;
use \Ilex\Base\Model\Core\BaseCore;

/**
 * Class AdminCore
 * @package Ilex\Base\Model\Core
 */
final class AdminCore extends BaseCore
{
    protected static $methodsVisibility = [
        self::V_PUBLIC => [
            'countCollection',
            'getCollection',
        ],
    ];

    protected function countCollection($input)
    {
        $this->loadCollection($input['collection_name']);
        $collection_name = $input['collection_name'] . 'Collection';
        unset($input['collection_name']);
        $criterion       = TRUE === isset($input['Criterion']) ? $input['Criterion'] : [];
        $skip            = TRUE === isset($input['Skip']) ? $input['Skip'] : NULL;
        $limit           = TRUE === isset($input['Limit']) ? $input['Limit'] : NULL;
        if (TRUE === is_null($skip) AND TRUE === isset($input['skip'])) $skip = $input['skip'];
        if (FALSE === is_null($skip)) $skip = intval($skip);
        unset($input['skip']);

        if (TRUE === is_null($limit) AND TRUE === isset($input['limit'])) $limit = $input['limit'];
        if (FALSE === is_null($limit)) $limit = intval($limit);
        unset($input['limit']);
        $criterion = array_merge($criterion, Kit::recoverMongoDBQuery($input));


        $data = $this->$collection_name->count($criterion, $skip, $limit);

        if (FALSE === is_numeric($data))
            $data = Kit::generateError('$data is not numeric.');
    }

    protected static function getCollection($input)
    {
        $this->loadCollection($input['collection_name']);
        $collection_name = $input['collection_name'] . 'Collection';
        unset($input['collection_name']);
        $criterion       = TRUE === isset($input['Criterion']) ? $input['Criterion'] : [];
        $projection      = TRUE === isset($input['Projection']) ? $input['Projection'] : [];
        $sort_by         = TRUE === isset($input['SortBy']) ? $input['SortBy'] : NULL;
        $skip            = TRUE === isset($input['Skip']) ? $input['Skip'] : NULL;
        $limit           = TRUE === isset($input['Limit']) ? $input['Limit'] : NULL;
        if (TRUE === is_null($skip) AND TRUE === isset($input['skip'])) $skip = $input['skip'];
        if (FALSE === is_null($skip)) $skip = intval($skip);
        unset($input['skip']);

        if (TRUE === is_null($limit) AND TRUE === isset($input['limit'])) $limit = $input['limit'];
        if (FALSE === is_null($limit)) $limit = intval($limit);
        unset($input['limit']);
        $criterion = array_merge($criterion, Kit::recoverMongoDBQuery($input));


        $data = $this->$collection_name->get($criterion, $projection, $sort_by, $skip, $limit);

        Kit::ensureDict($data);
    }
}