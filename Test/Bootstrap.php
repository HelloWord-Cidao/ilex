<?php

namespace Ilex\Test;

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('html_errors', 0);
session_cache_expire(240);
// define('ENVIRONMENT', 'TESTILEX');

require_once(__DIR__ . '/../../../autoload.php');
require_once(__DIR__ . '/RouterTester.php');
require_once(__DIR__ . '/ValidatorTester.php');

use \Ilex\Tester;
use \Ilex\Test\RouterTester as RT;
use \Ilex\Test\ValidatorTester as VT;

Tester::boot(__DIR__ . '/app', __DIR__ . '/runtime', 'app');

// RT::testHelloWorld();
// RT::testPost();
// RT::testCallingController();
// RT::testControllerIndex();
// RT::testControllerFunction();
// RT::testControllerResolve();
// RT::testGroup();
// echo 'Router Test Passed.' . PHP_EOL;

// VT::test('countCollection');

// echo 'Validator Test Passed.' . PHP_EOL;


use \ReflectionClass;
use \Ilex\Lib\UserException;
use \Ilex\Lib\Kit;

function get_method_type($method_mapping, $method_name)
{
    if (count(array_intersect($method_mapping['public'], $method_mapping['protected'])) > 0)
        throw new UserException('public duplicate protected');
    if (count(array_intersect($method_mapping['protected'], $method_mapping['private'])) > 0)
        throw new UserException('protected duplicate private');
    if (count(array_intersect($method_mapping['private'], $method_mapping['public'])) > 0)
        throw new UserException('private duplicate public');
    foreach (['public', 'protected', 'private'] as $type) {
        if (TRUE === in_array($method_name, $method_mapping[$type])) {
            return $type;
        }
    }
    throw new UserException('Method not found.');
}

function get_initiator($class_name, $method_name, $backtrace)
{

    return 'self';
    return 'descendant';
    return 'other';
}

class Base
{
    public function __call($method_name, $args)
    {
        print_r('in Base::__call');
        echo Kit::j([
            'line_number            ' => __LINE__,
            'current_class          ' => get_class(),
            'called_class           ' => get_called_class(),
            'called_class_short_name' => (new ReflectionClass(get_called_class()))->getShortName(),
            'method_name            ' => $method_name,
            'method_type            ' => get_method_type(static::$method_mapping, $method_name),
            'initiator              ' => get_initiator(get_called_class(), $method_name, debug_backtrace()),
            'args                   ' => $args,
            'backtrace              ' => debug_backtrace(),
        ]);
        call_user_func_array([get_called_class(), $method_name], $args);
    }

}

class A1 extends Base
{
    protected static $method_mapping = [
        'public' => [
            'a11',
        ],
        'protected' => [
            'a12',
        ],
        'private' => [
            'a13',
        ],
    ];

    protected function a11($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
    }

    protected function a12($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        // $this->__call('a13', [$p1, $p2]);
    }

    protected function a13($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
    }
}

class A2 extends A1
{
    protected static $method_mapping = [
        'public' => [
            'a21',
        ],
        'protected' => [
            'a22',
        ],
        'private' => [
            'a23',
        ],
    ];

    protected function a21($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        // $this->__call('a13', [$p1, $p2]);
    }

    protected function a22($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        // $this->__call('a13', [$p1, $p2]);
    }

    protected function a13($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
    }
}

class A3 extends A2
{
    protected static $method_mapping = [
        'public' => [
            'a31',
        ],
        'protected' => [
            'a32',
        ],
        'private' => [
            'a33',
        ],
    ];

    protected function a31($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        // $this->
    }

    protected function a32($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
    }

    protected function a33($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
    }
}

class B extends Base
{
    protected static $method_mapping = [
        'public' => [
            'b',
        ],
        'protected' => [
        ],
        'private' => [
        ],
    ];

    public function b($p1, $p2)
    {
        print_r('in ' . __METHOD__);
        echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        $a3 = new A3();
        $a3->a32($p1, $p2);
    }

}


try {
    // $a1 = new A1();
    // $a1->a11(111, 112);
    $b = new B();
    $b->b(1, 2);
} catch (UserException $e) {
    echo Kit::j(Kit::extractException($e));
}





