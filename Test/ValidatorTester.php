<?php

namespace Ilex\Test;

use PHPUnit_Framework_TestCase;
use \Ilex\Lib\Validator as V;

/**
 * Class ValidatorTester
 * @package Ilex\Test
 */
final class ValidatorTester extends PHPUnit_Framework_TestCase
{
    protected static $patternList = [
        'countCollection' => [
            V_CHILDREN => [
                'col' => [
                    V_TYPE  => V_TYPE_STRING,
                    V_VALUE => [
                        V_VALUE_REGEX => '@^[A-Z][A-Za-z]+$@',
                    ],
                ],
                'criterion' => [
                    V_EXISTENCE     => V_EXISTENCE_OPTIONAL,
                    V_TYPE             => V_TYPE_ARRAY,
                    V_DEFAULT_VALUE => [],
                ],
                'skip' => [
                    V_EXISTENCE     => V_EXISTENCE_OPTIONAL,
                    V_TYPE          => V_TYPE_INT,
                    V_VALUE         => [V_VALUE_GTE => 0],
                    V_DEFAULT_VALUE => [],
                ],
                'limit' => [
                    V_EXISTENCE     => V_EXISTENCE_OPTIONAL,
                    V_TYPE          => V_TYPE_INT,
                    V_VALUE         => [V_VALUE_GTE => 0],
                    V_DEFAULT_VALUE => [],
                ],
            ],
            V_REST => V_REST_REQUIRE,
        ]
    ];

    protected static $dataList = [
        'countCollection' => [
        ],
    ];

    final public static function test($name)
    {
        // print_r(self::$patternList);
        var_dump(V::validate(self::$patternList[$name], NULL));
    }
}