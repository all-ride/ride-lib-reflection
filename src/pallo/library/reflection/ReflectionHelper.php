<?php

namespace pallo\library\reflection;

use pallo\library\reflection\exception\ReflectionException;

use \Exception;
use \ReflectionClass;
use \ReflectionFunction;

/**
 * Helper for PHP's reflection
 */
class ReflectionHelper {

    /**
     * Gets a property of the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $default Default value to be returned when the property
     * is not set
     * @return mixed Value of the property if found, null otherwise
     */
    public function getProperty(&$data, $name, $default = null) {
        if (is_array($data)) {
            if (isset($data[$name])) {
                return $data[$name];
            }

            return $default;
        }

        $methodName = 'get' . ucfirst($name);
        if (method_exists($data, $methodName)) {
            return $data->$methodName();
        }

        if (isset($data->$name)) {
            return $data->$name;
        }

        return $default;
    }

    /**
     * Sets a property to the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $value Value for the property
     * @return null
     */
    public function setProperty(&$data, $name, $value) {
        if (is_array($data)) {
            $data[$name] = $value;

            return;
        }

        $methodName = 'set' . ucfirst($name);
        if (method_exists($data, $methodName)) {
            $data->$methodName($value);
        } else {
            $data->$name = $value;
        }
    }

    /**
     * Sets multiple properties to the provided data
     * @param array|object $data Data container
     * @param array $properties Array with the properties
     * @return null
     */
    public function setProperties(&$data, array $properties) {
    	foreach ($properties as $name => $value) {
    		$this->setProperty($data, $name, $value);
    	}
    }

    /**
     * Gets the possible arguments for a function/method call
     * @param mixed $class Class name for a static call, an instance for a
     * method call and null for a function call. When an instance of Callback
     * is provided, the class and method are taken from that instance.
     * @param string $method Name of the method in the class or if no class
     * provided, name of the function
     * @return array Array with the name of the argument as key and an
     * instance of ReflectionParameter as value
     */
    public function getArguments($class = null, $method = null) {
    	if ($class instanceof Callback && $method === null) {
    		$method = $class->getMethod();
    		$class = $class->getClass();
    	} elseif ($class && $method === null) {
    		$method = '__construct';
    	}

    	try {
	        if (!$class) {
	            $reflectionFunction = new ReflectionFunction($method);
	            $arguments = $reflectionFunction->getParameters();
	        } else {
	            $reflectionClass = new ReflectionClass($class);
	            $reflectionMethod = $reflectionClass->getMethod($method);
	            $arguments = $reflectionMethod->getParameters();
	        }
    	} catch (Exception $exception) {
    		throw new ReflectionException('Could not get the arguments', null, $exception);
    	}

        foreach ($arguments as $index => $argument) {
        	$arguments[$argument->getName()] = $argument;
        	unset($arguments[$index]);
        }

        return $arguments;
    }

}