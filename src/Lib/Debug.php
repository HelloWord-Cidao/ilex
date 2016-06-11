<?php

namespace Ilex\Lib;

use \Exception;
use \ReflectionFunction;
use \ReflectionMethod;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * @todo: method arg type validate
 * Class Debug
 * A class handling debug operations.
 * @package Ilex\Lib
 *
 * @property private static int     $traceCount
 * @property private static array   $traceStack
 * @property private static boolean $needSimplifyData
 * 
 * @method public static int        addTraceCount()
 * @method public static int        addToTraceStack(mixed $record)
 * @method public static int        clearTraceStack()
 * @method public static array      extractException(Exception $exception
 *                                      , $need_file_info = FALSE
 *                                      , $need_trace_info = FALSE
 *                                      , $need_previous_exception = TRUE)
 * @method public static boolean    getSimplifyData()
 * @method public static int        getTraceCount()
 * @method public static array      getTraceStack(boolean $reverse = TRUE)
 * @method public static array      recoverFunctionParameters(string|NULL $class_name
 *                                      , string|Closure $function_name, array $arg_list)
 * @method public static boolean    setSimplifyData(boolean $need_simplify_data)
 *
 * @method private static array extractInitiator(array $trace)
 */
final class Debug
{

    private static $traceCount = 0;
    private static $traceStack = [];
    private static $needSimplifyData = FALSE;

    /**
     * Extracts useful info from an exception.
     * @param Exception $exception
     * @param boolean   $need_file_info
     * @param boolean   $need_trace_info
     * @return array
     */
    public static function extractException($exception, $need_file_info = FALSE
        , $need_trace_info = FALSE, $need_previous_exception = TRUE)
    {
        $result    = [ 'message' => $exception->getMessage() ];
        $trace     = $exception->getTrace();
        $initiator = self::extractInitiator($trace);
        $trace = Kit::columns(
            self::recoverBacktraceParameters($trace), [ 'line', 'class', 'function', 'args' ], TRUE
        );
        if (TRUE === $need_file_info)
            $result = array_merge($result, [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        $result = array_merge($result, $initiator);
        $result = array_merge($result, $trace[0]);
        if (TRUE === $need_trace_info)
            $result = array_merge($result, [ 'trace' => $trace ]);
        if (FALSE === (FALSE === ($exception instanceof UserException) 
            OR (TRUE === ($exception instanceof UserException)
                AND TRUE === is_null($exception->getDetail()))))
            $result = array_merge($result, [ 'detail' => $exception->getDetail() ]);
        if (TRUE === $need_previous_exception AND FALSE  === is_null($exception->getPrevious()))
            $result = array_merge($result, [ 'previous' => self::extractException(
                  $exception->getPrevious(),
                  $need_file_info,
                  $need_trace_info,
                  $need_previous_exception
            ) ]);
        return $result;
    }

    /**
     * Extracts initiator info from a trace.
     * @param array $trace
     * @return array
     */
    private static function extractInitiator($trace)
    {
        if (count($trace) <= 1) $result = NULL;
        else {
            if (TRUE === in_array($trace[0]['function'], ['__call', 'call', 'callParent', 'execute'])) {
                if ($trace[0]['args'][0] !== $trace[1]['function']) $result = 1;
                else {
                    if (count($trace) <= 2) $result = NULL;
                    else $result = 2;
                }
            } elseif (TRUE === in_array($trace[1]['function'], [
                'call_user_func_array',
                'call_user_method_array',
                'call_user_func',
                'call_user_method'
            ])) {
                if (count($trace) <= 2) $result = NULL;
                else $result = 2;
            } else $result = 1;
        }
        return [
            'initiator_class'    => TRUE === is_null($result) ? NULL : $trace[$result]['class'],
            'initiator_function' => TRUE === is_null($result) ? NULL : $trace[$result]['function'],
        ];
    }
    
    /**
     * Recovers parameters of the function in the records of a backtrace.
     * @param array $backtrace
     * @return array
     */
    public static function recoverBacktraceParameters($backtrace)
    {
        foreach ($backtrace as $index => $record) {
            try {
                $backtrace[$index]['args'] = self::recoverFunctionParameters(
                    $record['class'],
                    $record['function'],
                    $record['args']
                );
                if (TRUE === self::getSimplifyData())
                    $backtrace[$index]['args'] = array_keys($backtrace[$index]['args']);
            } catch (Exception $e) {
                $backtrace[$index]['args'] = [
                    'raw_args' => $record['args'],
                    'recover'  => self::extractException($e, TRUE, FALSE, TRUE),
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
    public static function recoverFunctionParameters($class_name, $function_name, $arg_list)
    {
        $param_list = [];
        try {
            if (TRUE === is_null($class_name))
                $reflection_function = new ReflectionFunction($function_name);
            else $reflection_function = new ReflectionMethod($class_name, $function_name);
        } catch (Exception $e) {
            throw new UserException('Reflection failed.', NULL, $e);
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
                    // @todo: check if it will fail
                    $param_list[$param_name] = $param->getDefaultValue();
                } catch (Exception $e) {
                    // @todo: check it
                    // throw new UserException('Method(getDefaultValue) failed.', NULL, $e);
                    $param_list[$param_name] = '[GET_DEFAULT_VALUE_FAILED]';
                }
            }
            else $param_list[$param_name] = $arg_list[$position];
        }
        return $param_list;
    }

    /**
     * Clears the trace stack.
     * @return int Current size of the trace stack.
     */
    public static function clearTraceStack()
    {
        $result = count(self::$traceStack);
        self::$traceStack = [];
        self::$traceCount = 0;
        return $result;
    }

    /**
     * Increase the trace count by 1.
     * @return int Current trace count.
     */
    public static function addTraceCount()
    {
        self::$traceCount += 1;
        return self::$traceCount;
    }

    /**
     * Adds record to the trace stack.
     * @param mixed $record
     * @return int Current size of the trace stack.
     */
    public static function addToTraceStack($record)
    {
        self::$traceStack[] = $record;
        return count(self::$traceStack);
    }

    /**
     * Gets the trace count.
     * @return int Current trace count.
     */
    public static function getTraceCount()
    {
       return self::$traceCount;
    }

    /**
     * Gets the trace stack in reverse order.
     * @param boolean $reverse
     * @return array
     */
    public static function getTraceStack($reverse = TRUE)
    {
        if (TRUE === $reverse) return array_reverse(self::$traceStack);
        else return self::$traceStack;
    }

    /**
     * Sets whether it need to simplify data when outputing debug info.
     * @param boolean $need_simplify_data
     * @return  boolean
     */
    public static function setSimplifyData($need_simplify_data)
    {
        return (self::$needSimplifyData = $need_simplify_data);
    }

    /**
     * Gets whether it need to simplify data when outputing debug info.
     * @return boolean
     */
    public static function getSimplifyData()
    {
        return self::$needSimplifyData;
    }

}