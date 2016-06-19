<?php

namespace Ilex\Base;

use \ReflectionClass;
use \ReflectionMethod;
use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;
use \Ilex\Lib\UserException;

/**
 * Class Base
 * Base class of controllers and models.
 * @package Ilex\Base
 */
abstract class Base
{
    const V_PUBLIC     = 'V_PUBLIC';
    const V_PROTECTED  = 'V_PROTECTED';
    const V_PRIVATE    = 'V_PRIVATE';
    const T_SELF       = 'T_SELF';
    const T_DESCENDANT = 'T_DESCENDANT';
    const T_OTHER      = 'T_OTHER';

    protected $configModelName = NULL;
    protected $dataModelName   = NULL;

    final protected function loadConfig($path)
    {
        $config_model_name = Loader::getHandlerFromPath($path) . 'Config';
        if (TRUE === is_null($this->$config_model_name)) {
            try {
                $this->$config_model_name = Loader::loadConfig($path);
            } catch (Exception $e) {
                $config_model_name = 'DefaultConfig';
                $this->$config_model_name = Loader::loadConfig('Default');
            }
        }
        $this->configModelName = $config_model_name;
        return $config_model_name;
    }

    final protected function loadData($path)
    {
        $data_model_name = Loader::getHandlerFromPath($path) . 'Data';
        if (TRUE === is_null($this->$data_model_name)) {
            try {
                $this->$data_model_name = Loader::loadData($path);
            } catch (Exception $e) {
                $data_model_name = 'DefaultData';
                $this->$data_model_name = Loader::loadData('Default');
            }
        }
        $this->dataModelName = $data_model_name;
        return $data_model_name;
    }

    final protected function loadCore($path)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Core';
        return ($this->$handler_name
            = Loader::loadCore($path));
    }

    final protected function loadCollection($path, $with_instantiate = FALSE)
    {
        $handler_name = Loader::getHandlerFromPath($path) . 'Collection';
        return ($this->$handler_name
            = Loader::loadCollection($path, $with_instantiate));
    }

    final protected function includeEntity($path)
    {
        return Loader::includeEntity($path);
    }

    final protected function generateExecutionRecord($class_name, $method_name)
    {
        $class                = new ReflectionClass($class_name);
        if (FALSE === $class->hasMethod($method_name))
            throw new UserException("Method($method_name) does not exist in class($class_name).");
        $method               = new ReflectionMethod($class_name, $method_name);
        if (TRUE === $method->isPublic())
            throw new UserException("Method($method_name) in class($class_name) is public.");
        $declaring_class      = $method->getDeclaringClass();
        $declaring_class_name = $declaring_class->getName();
        $methods_visibility   = $declaring_class->getStaticProperties()['methodsVisibility'];
        $method_visibility    = $this->getMethodVisibility($methods_visibility, $method_name);

        list($initiator_class_name, $initiator_type)
            = $this->getInitiatorNameAndType($method_name, $declaring_class);
        $method_accessibility = $this->getMethodAccessibility($method_visibility, $initiator_type);
        $handler_prefix       = Loader::getHandlerPrefixFromPath($declaring_class_name);
        $handler_suffix       = Loader::getHandlerSuffixFromPath($declaring_class_name);
        $execution_record = [
            'success'              => FALSE,
            'class'                => $class_name,
            'method'               => $method_name,
            'method_accessibility' => $method_accessibility,
            'declaring_class'      => $declaring_class_name,
            'method_visibility'    => $method_visibility,
            'initiator_class'      => $initiator_class_name,
            'initiator_type'       => $initiator_type,
            'handler_prefix'       => $handler_prefix,
            'handler_suffix'       => $handler_suffix,
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
            throw new UserException('Public duplicates protected.', $methods_visibility);
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
        $backtrace          = Kit::columns(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20),
            [ 'class', 'function' ], TRUE);
        $initiator_name     = NULL;
        foreach ($backtrace as $record) {
            if (TRUE === is_null($record['class']) 
                OR  TRUE === in_array(
                    $record['class'], [
                        'Ilex\\Base\\Base',
                        'Ilex\\Base\\Controller\\Service\\BaseService',
                        'Ilex\\Base\\Model\\Feature\\BaseFeature',
                    ]
                )
            ) continue;
            if ($method_name !== $record['function']) {
                $initiator_name = $record['class'];
                break;
            }
        }
        if (TRUE === is_null($initiator_name)) {
            $result = [ $initiator_name, self::T_OTHER ];
        } else {
            $initiator = new ReflectionClass($initiator_name);
            $declaring_class_name = $declaring_class->getName();
            if ($initiator_name === $declaring_class_name) {
                $result = [ $initiator_name, self::T_SELF ];
            } elseif (TRUE === $initiator->isSubclassOf($declaring_class_name)) {
                $result = [ $initiator_name, self::T_DESCENDANT ];
            } else $result = [ $initiator_name, self::T_OTHER ];
        }
        // var_dump([$method_name, $declaring_class, $initiator_name]);
        // var_dump($result);
        // var_dump($backtrace);
        return $result;
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