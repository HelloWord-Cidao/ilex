<?php

namespace Ilex\Test;

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('html_errors', 0);
session_cache_expire(240);
// define('ENVIRONMENT', 'TESTILEX');

require_once(__DIR__ . '/../../../autoload.php');

use \Exception;
use \ReflectionClass;
use \ReflectionMethod;
use \Ilex\Lib\UserException;
use \Ilex\Lib\Kit;

abstract class Base
{

    const V_PUBLIC     = 'V_PUBLIC';
    const V_PROTECTED  = 'V_PROTECTED';
    const V_PRIVATE    = 'V_PRIVATE';
    const T_SELF       = 'T_SELF';
    const T_DESCENDANT = 'T_DESCENDANT';
    const T_OTHER      = 'T_OTHER';

    final public function __call($method_name, $arg_list)
    {
        print_r('in' . __METHOD__ . PHP_EOL);

        $execution_record     = self::prepareExecutionRecord($method_name, $arg_list);
        $class_name           = $execution_record['class'];
        $method_accessibility = $execution_record['method_accessibility'];
        if (FALSE === $method_accessibility) 
            throw new UserException('Method is not accessible.', $execution_record);
        try {
            $execution_record['result'] = $result
                = call_user_func_array([$class_name, $method_name], $arg_list);
        } catch (Exception $e) {
            throw new UserException('Feature execution failed.', $execution_record, $e);
        }
        Kit::addToTraceStack($execution_record);
    }

    final private function prepareExecutionRecord($method_name, $arg_list)
    {
        $class_name           = get_called_class();
        $class                = new ReflectionClass($class_name);
        if (FALSE === $class->hasMethod($method_name))
            throw new UserException("Method($method_name) does not exist in class($class_name).");
        $method               = new ReflectionMethod($class_name, $method_name);
        $declaring_class      = $method->getDeclaringClass();
        $declaring_class_name = $declaring_class->getName();
        $methods_visibility   = $declaring_class->getConstant('METHODS_VISIBILITY');
        $method_visibility    = self::getMethodVisibility($methods_visibility, $method_name);
        $param_list           = Kit::recoverFunctionParameters($class_name, $method_name, $arg_list);
        list($initiator_class_name, $initiator_type)
            = self::getInitiatorNameAndType($method_name, $declaring_class);
        $method_accessibility = self::getMethodAccessibility($method_visibility, $initiator_type);

        $execution_record = [
            'class'                => $class_name,
            'method'               => $method_name,
            'param'                => $param_list,
            'method_accessibility' => $method_accessibility,
            'declaring_class'      => $declaring_class_name,
            'method_visibility'    => $method_visibility,
            'initiator_class'      => $initiator_class_name,
            'initiator_type'       => $initiator_type,
        ];

        return $execution_record;
    }

    final private function getMethodVisibility($methods_visibility, $method_name)
    {
        if (TRUE === isset($methods_visibility[self::V_PUBLIC])
            AND TRUE === isset($methods_visibility[self::V_PROTECTED])
            AND count(array_intersect(
                $methods_visibility[self::V_PUBLIC],
                $methods_visibility[self::V_PROTECTED])) > 0)
            throw new UserException('Public duplicates protected.');
        foreach ([self::V_PUBLIC, self::V_PROTECTED] as $type) {
            if (TRUE === isset($methods_visibility[$type])
                AND TRUE === in_array($method_name, $methods_visibility[$type])) {
                return $type;
            }
        }
        return self::V_PRIVATE;
    }

    final private function getInitiatorNameAndType($method_name, $declaring_class)
    {
        $backtrace          = debug_backtrace();
        $current_class_name = get_class();
        $initiator_name     = NULL;
        foreach ($backtrace as $record) {
            if (TRUE === is_null($record['class']) OR $current_class_name === $record['class'])
                continue;
            if ($method_name != $record['function']) {
                $initiator_name = $record['class'];
                break;
            }
        }
        if (TRUE === is_null($initiator_name))
            return [ $initiator_name, self::T_OTHER ];
        $initiator = new ReflectionClass($initiator_name);
        $declaring_class_name = $declaring_class->getName();
        if ($initiator_name === $declaring_class_name) {
            return [ $initiator_name, self::T_SELF ];
        } elseif (TRUE === $initiator->isSubclassOf($declaring_class_name)) {
            return [ $initiator_name, self::T_DESCENDANT ];
        } else return [ $initiator_name, self::T_OTHER ];
    }

    final private function getMethodAccessibility($method_visibility, $initiator_type)
    {
        if (self::V_PUBLIC === $method_visibility) {
            return TRUE;
        } elseif (self::V_PROTECTED === $method_visibility) {
            if (self::T_OTHER === $initiator_type) {
                return FALSE;
            } else return TRUE;

        } elseif (self::V_PRIVATE === $method_visibility) {
            if (self::T_SELF === $initiator_type) {
                return TRUE;
            } else return FALSE;
        }
        throw new UserException('Method accessibility calculation failed.');
    }
}

class A1 extends Base
{
    const METHODS_VISIBILITY = [
        self::V_PUBLIC    => [
            'a11',
        ],
        self::V_PROTECTED => [
            'a12',
        ],
    ];

    protected function a11($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        return 11;
    }

    protected function a12($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        // $this->__call('a13', [$p1, $p2]);
        return 12;
    }

    protected function a13($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        return 13;
    }
}

class A2 extends A1
{
    const METHODS_VISIBILITY = [
        self::V_PUBLIC    => [
            'a21',
        ],
        self::V_PROTECTED => [
            'a22',
        ],
    ];

    protected function a21($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        // $this->__call('a13', [$p1, $p2]);
        return 21;
    }

    protected function a22($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        $this->__call('a12', [$p1, $p2]);
        return 22;
    }

    protected function a13($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        return 23;
    }
}

class A3 extends A2
{
    const METHODS_VISIBILITY = [
        self::V_PUBLIC    => [
            'a31',
        ],
        self::V_PROTECTED => [
            'a32',
        ],
    ];

    protected function a31($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        $this->__call('a32', [$p1, $p2]);
        return 31;
    }

    protected function a32($p1 = 'peach', $p2 = 'hello')
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        $this->__call('a22', [$p1, $p2]);
        return 32;
    }

    protected function a33($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        return 33;
    }
}

class B extends Base
{
    const METHODS_VISIBILITY = [
        self::V_PUBLIC => [
            'b',
        ],
    ];

    public function b($p1, $p2)
    {
        print_r('in ' . __METHOD__ . PHP_EOL);
        // echo Kit::j([__LINE__, get_class(), get_called_class(), func_get_args()]);
        $a3 = new A3();
        $a3->a31(NULL, $p2);
        return 'b';
    }

}

Kit::clearTraceStack();
try {
    // $a1 = new A1();
    // $a1->a11(111, 112);
    $b = new B();
    $b->b(1, 2);
} catch (Exception $e) {
    echo Kit::j(Kit::extractException($e, TRUE, FALSE, TRUE));
}
echo Kit::j(Kit::getTraceStack());
