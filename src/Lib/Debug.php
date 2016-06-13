<?php

namespace Ilex\Lib;

use \Exception;
use \ReflectionFunction;
use \ReflectionMethod;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * @todo: method arg type validate
 * Class Debug
 * A class handling debug operations.
 * @package Ilex\Lib
 *
 * @property private static array   $traceStack
 * 
 * @method public static int   addExecutionRecord(mixed $execution_record)
 * @method public static int   clear()
 * @method public static int   countExecutionRecord()
 * @method public static array extractException(Exception $exception)
 * @method public static array getExecutionRecordStack(boolean $reverse = TRUE)
 *
 * @method private static array recoverBacktraceParameters(array $backtrace)
 * @method private static array recoverFunctionParameters(string|NULL $class_name
 *                                      , string|Closure $function_name, array $arg_list)
 * @method private static array extractInitiator(array $trace)
 */
final class Debug
{
    const D_ALL  = 1023;
    const D_NONE = 0;

    const E_DEVELOPMENT = 'DEVELOPMENT';
    const E_PRODUCTION  = 'PRODUCTION';
    const E_TEST        = 'TEST';

    private static $flag                 = 0;
    private static $executionRecordStack = [];
    private static $environment          = self::E_PRODUCTION;

    public static function setDisplay($flag)
    {
        self::$flag = $flag;
    }

    public static function checkDisplay($flag)
    {
        return (self::$flag & $flag) === $flag;
    }

    public static function setEnvironmentToDevelopment()
    {
        self::$environment = self::E_DEVELOPMENT;
    }

    public static function setEnvironmentToProduction()
    {
        self::$environment = self::E_PRODUCTION;
    }

    public static function setEnvironmentToTest()
    {
        self::$environment = self::E_TEST;
    }

    public static function isDevelopment()
    {
        return self::$environment === self::E_DEVELOPMENT;
    }

    public static function isProduction()
    {
        return self::$environment === self::E_PRODUCTION;
    }

    public static function isTest()
    {
        return self::$environment === self::E_TEST;
    }

    /**
     * Clears the execution record stack.
     */
    public static function clear()
    {
        self::$executionRecordStack = [];
    }

    /**
     * Adds execution record to the stack.
     * @param mixed $execution_record
     * @return int Current size of the execution record stack.
     */
    public static function addExecutionRecord($execution_record)
    {
        if (TRUE === isset($execution_record['args'])) {
            $class_name  = $execution_record['class'];
            $method_name = $execution_record['method'];
            $arg_list    = $execution_record['args'];
            try {
                $param_list = self::recoverFunctionParameters($class_name, $method_name, $arg_list);
            } catch (Exception $e) {
                $param_list = [
                    'raw_args' => $arg_list,
                    'recover'  => self::extractException($e),
                ];
                // throw new UserException('Method(recoverFunctionParameters) failed.', NULL, $e);
            }
            $execution_record['params'] = $param_list;
            unset($execution_record['args']);
        }
        self::$executionRecordStack[] = $execution_record;
        return self::countExecutionRecord();
    }

    /**
     * Counts the execution record stack.
     * @return int
     */
    public static function countExecutionRecord()
    {
       return count(self::$executionRecordStack);
    }

    /**
     * Gets the execution record stack.
     * @param boolean $reverse
     * @return array
     */
    public static function getExecutionRecordStack($reverse = TRUE)
    {
        $result = self::$executionRecordStack;
        if (TRUE === $reverse) $result = array_reverse($result);
        $index = 0;
        while ($index < count($result)) {
            $result[$index] = sprintf('%02d. (%s) %10s %10s :: %s',
                $index,
                (TRUE === $result[$index]['success']) ? 'ok' : 'error',
                // $result[$index]['class'],
                $result[$index]['handler_prefix'],
                $result[$index]['handler_suffix'],
                $result[$index]['method']
            );
            $index++;
        }
        $result = array_merge([ $result[0] ], array_slice($result, 7));
        // 'success'
        // 'class'
        // 'method'

        // 'input'
        // 'input_validation_result'
        // 'input_sanitization_result'
        // 'params'
        // 'args_validation_result'
        // 'args_sanitization_result'

        // 'feature_privilege_validation_result'
        // 'method_accessibility'
        // 'method_visibility'
        // 'declaring_class'
        // 'initiator_class'
        // 'initiator_type'
        // 'handler_prefix'
        // 'handler_suffix'

        // 'result'
        // 'result_validation_result'
        // 'result_sanitization_result'
        // 'service_result'
        // 'service_result_validation_result'
        // 'service_result_sanitization_result'

        // 'is_time_consuming'
        return $result;
    }

    /**
     * Extracts useful info from an exception.
     * @param Exception $exception
     * @return array
     */
    public static function extractException($exception)
    {
        $result = [ self::extractExceptionIteratively($exception) ];
        $index = 0;
        while ($index < count($result)) {
            if (TRUE === isset($result[$index]['previous']))
                $result[] = $result[$index]['previous'];
            try {
                $handler_prefix = Loader::getHandlerPrefixFromPath(
                    $result[$index]['class'], ['Service', 'Feature', 'Core', 'Collection', 'Log']);
                $handler_suffix = Loader::getHandlerSuffixFromPath(
                    $result[$index]['class'], ['Service', 'Feature', 'Core', 'Collection', 'Log']);
                $handler = sprintf('%10s %10s', $handler_prefix, $handler_suffix);
            } catch (Exception $e) {
                $handler = $result[$index]['class'];
            }
            $result[$index] = sprintf('%d. %s :: %s (%d) -> [%s]', 
                $index,
                $handler,
                $result[$index]['function'],
                $result[$index]['line'],
                $result[$index]['message']
            );
            $index++;
        }
        // message
        // file
        // line
        // class
        // function
        // params
        // initiator_class
        // initiator_function
        // trace
        // detail
        return $result;
    }

    /**
     * Extracts useful info from an exception iteratively.
     * @param Exception $exception
     * @return array
     */
    private static function extractExceptionIteratively($exception)
    {
        $result = [
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => Kit::columns(
                self::recoverBacktraceParameters($exception->getTrace()),
                [ 'line', 'class', 'function', 'params' ], TRUE
            ),
        ];
        // add fields: 'initiator_class', 'initiator_function'
        $result += self::extractInitiator($result['trace']);
        // add fields: 'class', 'function', 'params'
        $result += $result['trace'][0];

        if (FALSE === (FALSE === ($exception instanceof UserException) 
            OR (TRUE === ($exception instanceof UserException)
                AND TRUE === is_null($exception->getDetail())))
        ) {
            $result['detail'] = $exception->getDetail();
        }
        if (FALSE === is_null($exception->getPrevious()))
            $result['previous'] = self::extractExceptionIteratively($exception->getPrevious());
        return $result;
    }

    /**
     * Extracts initiator info from a trace.
     * @param array $trace
     * @return array
     */
    private static function extractInitiator($trace)
    {
        if (count($trace) <= 1) $index = NULL;
        else {
            // @todo: add comment to this
            if (TRUE === in_array($trace[0]['function'], [
                '__call', 'call', 'callParent', 'execute'
            ])) {
                if ($trace[0]['args'][0] !== $trace[1]['function']) $index = 1;
                else {
                    if (count($trace) <= 2) $index = NULL;
                    else $index = 2;
                }
            } elseif (TRUE === in_array($trace[1]['function'], [
                'call_user_func_array',
                'call_user_method_array',
                'call_user_func',
                'call_user_method'
            ])) {
                if (count($trace) <= 2) $index = NULL;
                else $index = 2;
            } else $index = 1;
        }
        return [
            'initiator_class'    => TRUE === is_null($index) ? NULL : $trace[$index]['class'],
            'initiator_function' => TRUE === is_null($index) ? NULL : $trace[$index]['function'],
        ];
    }
    
    /**
     * Recovers parameters of the function in the records of a backtrace.
     * @param array $backtrace
     * @return array
     */
    private static function recoverBacktraceParameters($backtrace)
    {
        foreach ($backtrace as $index => $record) {
            try {
                $backtrace[$index]['params'] = self::recoverFunctionParameters(
                    $record['class'],
                    $record['function'],
                    $record['args']
                );
            } catch (Exception $e) {
                $backtrace[$index]['params'] = [
                    'raw_args' => $record['args'],
                    'recover'  => self::extractException($e),
                ];
                // throw new UserException('Method(recoverFunctionParameters) failed.', NULL, $e);
            }
        };
        return $backtrace;
    }

    /**
     * Recovers parameters of a function or a method in a class.
     * @param string|NULL    $class_name
     * @param string|Closure $function_name
     * @param array          $arg_list
     * @return array
     */
    private static function recoverFunctionParameters($class_name, $function_name, $arg_list)
    {
        $param_mapping = [];
        try {
            if (TRUE === is_null($class_name))
                $reflection_function = new ReflectionFunction($function_name);
            else $reflection_function = new ReflectionMethod($class_name, $function_name);
        } catch (Exception $e) {
            // throw new UserException('Reflection failed.', [ $class_name, $function_name ], $e);
            return NULL;
        }
        foreach ($reflection_function->getParameters() as $position => $param) {
            $param_name = $param->getName();
            // var_dump([
            //     'index'                       => $position, 
            //     'position'                    => $param->getPosition(), 
            //     'name'                        => $param->getName(), 
            //     'is_optional'                 => $param->isOptional(),
            //     'is_passed_by_reference'      => $param->isPassedByReference(),
            //     'allows_null'                 => $param->allowsNull(), 
            //     'default_value_constant_name' => $param->isDefaultValueConstant(),
            //          // ? $param->getDefaultValueConstantName() : 'default value is not const', 
            //     'default_value'               => $param->isDefaultValueAvailable(),
            //          // ? $param->getDefaultValue() : 'no default value', 
            //     'arg'                         => $arg_list[$position],
            // ]);
            if ($position + 1 > count($arg_list)) {
                try {
                    // @TODO: check if it will fail
                    $param_mapping[$param_name] = $param->getDefaultValue();
                } catch (Exception $e) {
                    // @TODO: check it, add alert to postman
                    // throw new UserException('Method(getDefaultValue) failed.', NULL, $e);
                    $param_mapping[$param_name] = '[GET_DEFAULT_VALUE_FAILED]';
                }
            }
            else $param_mapping[$param_name] = $arg_list[$position];
        }
        return $param_mapping;
    }

}