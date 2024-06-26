<?php

namespace ride\library\reflection;

use ride\library\reflection\exception\ReflectionException;

use \Exception;
use \ReflectionClass;
use \ReflectionFunction;
use \ReflectionProperty;

/**
 * Helper for PHP's reflection
 */
class ReflectionHelper implements Invoker {

    /**
     * Loaded reflection classes
     * @var array
     */
    private $classes = array();

    /**
     * Loaded reflection properties
     * @var array
     */
    private $properties = array();

    /**
     * Returns the fields to serialize
     * @return array Array with field names
     */
    public function __sleep() {
        return array();
    }

    /**
     * Reinitializes this field after sleeping
     * @return null
     */
    public function __wakeup() {

    }

    /**
     * Checks if the provided class extends or implements the provided interface
     * @param mixed $class Class name, instance or a reflection class to test
     * @param string $neededInterface Class name of the needed interface
     * @return boolean True when the class is valid
     * @throws \ride\library\reflection\exception\ReflectionException when the
     * class does not implement or extend the needed interface or when one of
     * the classes does not exist
     */
    public function implementsOrExtends($class, $neededInterface) {
        try {
            if ($class instanceof ReflectionClass) {
                $reflectionClass = $class;
            } elseif (is_string($class)) {
                $reflectionClass = $this->getReflectionClass($class);
            } elseif (is_object($class)) {
                $reflectionClass = $this->getReflectionClass(get_class($class));
            } else {
                throw new ReflectionException('Invalid class provided');
            }
        } catch (Exception $exception) {
            throw new ReflectionException('Could not check class hierarchy: invalid class provided', 0, $exception);
        }

        return $this->checkImplementsOrExtends($reflectionClass, $neededInterface);
    }

    /**
     * Creates an instance of the provided class
     * @param string $class Full name of the class
     * @param array|null $arguments Named arguments for the constructor
     * @param string|null $neededInterface Full name of the interface or parent class
     * @return mixed New instance of the requested class
     * @throws Exception when an invalid argument is provided
     * @throws Exception when the class does not exists
     * @throws Exception when the class does not implement/extend the provided
     * @throws Exception when a mandatory construct parameter is missing in the
     * provided parameters
     * needed class
     */
    public function createObject($class, array $arguments = null, $neededInterface = null) {
        // validate the class
        if (!is_string($class) || !$class) {
            throw new ReflectionException('Could not create object: provided class is empty or not a string');
        }

        try {
            $reflectionClass = $this->getReflectionClass($class);
        } catch (Exception $exception) {
            throw new ReflectionException('Could not create object: class ' . $class . ' not found', 0, $exception);
        }

        // validate class inheritance with the needed class
        if ($neededInterface) {
            try {
                $this->checkImplementsOrExtends($reflectionClass, $neededInterface);
            } catch (Exception $exception) {
                throw new ReflectionException('Could not create object: ' . $class . ' does not have the right inheritance', 0, $exception);
            }
        }

        // validate the constructor parameters
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor && $arguments) {
            throw new ReflectionException('Could not create ' . $class . ': constructor parameters provided while there is no constructor');
        } elseif (!$constructor) {
            // no constructor, create and return the object instance
            return $reflectionClass->newInstance();
        }

        $constructorArguments = $constructor->getParameters();
        if (!$constructorArguments && !$arguments) {
            // no arguments, create and return the object instance
            return $reflectionClass->newInstance();
        }

        foreach ($constructorArguments as $index => $constructorArgument) {
            $name = $constructorArgument->getName();

            if ($arguments && (isset($arguments[$name]) || array_key_exists($name, $arguments) !== false)) {
                $constructorArguments[$name] = $arguments[$name];

                unset($constructorArguments[$index]);
                unset($arguments[$name]);
            } elseif (!$constructorArgument->isOptional()) {
                throw new ReflectionException('Could not create ' . $class . ': mandatory constructor parameter ' . $name . ' is not provided');
            } else {
                $constructorArguments[$name] = $constructorArgument->getDefaultValue();

                unset($constructorArguments[$index]);
            }
        }

        if ($arguments) {
            throw new ReflectionException('Could not create ' . $class . ': invalid constructor parameters provided (' . implode(', ', array_keys($arguments)) . ')');
        }

        // create and return object instance with constructor parameters
        return $reflectionClass->newInstanceArgs($constructorArguments);
    }

    /**
     * Checks if the provided class extends or implements the provided interface
     * @param ReflectionClass $reflectionClass Instance of a reflection class
     * @param string $neededInterface Class name of the needed interface
     * @return boolean True when the class is valid
     * @throws \ride\library\reflection\exception\ReflectionException when the
     * class does not implement or extend the needed interface or when one of
     * the classes does not exist
     */
    protected function checkImplementsOrExtends(ReflectionClass $reflectionClass, $neededInterface) {
        $class = $reflectionClass->getName();
        if ($class === $neededInterface) {
            return true;
        }

        if (!is_string($neededInterface)) {
            throw new ReflectionException('Could not check class hierarchy: provided needed interface is empty or not a string');
        }

        try {
            $neededReflectionClass = $this->getReflectionClass($neededInterface);
        } catch (Exception $exception) {
            throw new ReflectionException('Could not check class hierarchy: needed interface ' . $neededInterface . ' not found', 0, $exception);
        }

        if ($neededReflectionClass->isInterface() && !$reflectionClass->implementsInterface($neededInterface)) {
            throw new ReflectionException($class . ' does not implement ' . $neededInterface);
        } elseif (!$reflectionClass->isSubclassOf($neededInterface)) {
            throw new ReflectionException($class . ' does not extend ' . $neededInterface);
        }

        return true;
    }

    /**
     * Creates a data instance
     * @param string $class Full name of the data class
     * @param array $values Values for the data
     * @param boolean $useReflection Set to true to skip setters
     * @return mixed Instance of the data object
     * @throws Exception when the data class could not be created
     * @throws Exception when a mandatory constructor parameter is missing in
     * the provided properties
     */
    public function createData($class, array $properties, $useReflection = false) {
        // gather the constructor parameters
        $arguments = $this->getArguments('__construct', $class);
        foreach ($arguments as $name => $argument) {
            if (isset($properties[$name]) || array_key_exists($name, $properties) !== false) {
                $arguments[$name] = $properties[$name];

                unset($properties[$name]);
            } elseif (!$argument->isOptional()) {
                throw new ReflectionException('Could not create ' . $class . ': mandatory construct parameter ' . $name . ' is not set in the provided properties');
            } else {
                $arguments[$name] = $argument->getDefaultValue();
            }
        }

        // create the data instance
        $data = $this->createObject($class, $arguments);

        // set the remaining properties
        foreach ($properties as $name => $value) {
            $this->setProperty($data, $name, $value, $useReflection);
        }

        return $data;
    }

    /**
     * Gets a property of the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $default Default value to be returned when the property
     * is not set
     * @param boolean $useReflection Set to true to skip getter
     * @return mixed Value of the property if found, null otherwise
     */
    public function getProperty(&$data, $name, $default = null, $useReflection = false) {
        $isArray = is_array($data);
        if ($name == '' || (!is_string($name) && ($isArray && !is_numeric($name)))) {
            throw new ReflectionException('Could obtain property: invalid name provided');
        }

        if ($isArray) {
            return $this->getArrayProperty($data, $name, $default);
        }

        if ($useReflection) {
            try {
                $property = $this->getReflectionProperty($data, $name);

                return $property->getValue($data);
            } catch (Exception $exception) {

            }
        } else {
            $methodName = 'get' . ucfirst($name);
            if (method_exists($data, $methodName)) {
                return $data->$methodName();
            } elseif (strncmp($name, 'is', 2) === 0 && method_exists($data, $name)) {
                return $data->$name();
            }

            if (isset($data->$name)) {
                return $data->$name;
            }
        }

        return $default;
    }

    /**
     * Gets the property of an array
     * @param array $data Data array
     * @param string $name Name of the property, can be something like name[sub]
     * @param mixed $default Default value when the property is not set
     * @return mixed
     * @throws \ride\library\reflection\exception\ReflectionException
     */
    protected function getArrayProperty(array &$data, $name, $default = null) {
        $positionOpen = strpos($name, '[');
        if ($positionOpen === false) {
            return $this->getArrayValue($data, $name, $default);
        } elseif ($positionOpen === 0) {
            throw new ReflectionException('Could not get property ' . $name . ': name cannot start with [');
        }

        $tokens = explode('[', $name);

        $value = $data;
        $token = array_shift($tokens) . ']';
        while ($token != null) {
            $token = $this->parseArrayToken($token, $name);

            $value = $this->getArrayValue($value, $token);
            if ($value === null) {
                return $default;
            }

            $token = array_shift($tokens);
        }

        return $value;
    }

    /**
     * Gets a value from a array
     * @param array $array
     * @param string $key
     * @return null|mixed Value if the key was set, null otherwise
     */
    protected function getArrayValue(array $array, $key, $default = null) {
        if (!isset($array[$key])) {
            return $default;
        }

        return $array[$key];
    }

    /**
     * Sets a property to the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $value Value for the property
     * @param boolean $useReflection Set to true to skip setter
     * @return null
     */
    public function setProperty(&$data, $name, $value, $useReflection = false) {
        $isArray = is_array($data);
        if ($name == '' || (!is_string($name) && ($isArray && !is_numeric($name)))) {
            throw new ReflectionException('Could obtain property: invalid name provided');
        }

        if ($isArray) {
            return $this->setArrayProperty($data, $name, $value);
        }

        if ($useReflection) {
            try {
                $property = $this->getReflectionProperty($data, $name);
                $property->setValue($data, $value);
            } catch (Exception $exception) {
                throw new ReflectionException('Could set property: reflection exception occured', 0, $exception);
            }
        } else {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($data, $methodName)) {
                $data->$methodName($value);
            } else {
                $data->$name = $value;
            }
        }
    }

    /**
     * Sets an array property
     * @param array $data
     * @param string $name
     * @param mixed $value
     * @throws ReflectionException
     */
    protected function setArrayProperty(array &$data, $name, $value) {
        $positionOpen = strpos($name, '[');
        if ($positionOpen === false) {
            $data[$name] = $value;

            return;
        } elseif ($positionOpen === 0) {
            throw new ReflectionException('Could not get property ' . $name . ': name cannot start with [');
        }

        $tokens = explode('[', $name);

        $array = &$data;
        $previousArray = &$array;

        $token = array_shift($tokens) . ']';
        $token = $this->parseArrayToken($token, $name);

        while (!empty($tokens)) {
            if (isset($array[$token]) && is_array($array[$token])) {
                $array = &$array[$token];
            } else {
                $previousArray[$token] = array();
                $array = &$previousArray[$token];
            }

            $previousArray = &$array;
            $token = $this->parseArrayToken(array_shift($tokens), $name);
        }

        $array[$token] = $value;
    }

    /**
     * Sets multiple properties to the provided data
     * @param array|object $data Data container
     * @param array $properties Array with the properties
     * @param boolean $useReflection Set to true to skip setters
     * @return null
     */
    public function setProperties(&$data, array $properties, $useReflection = false) {
        foreach ($properties as $name => $value) {
            $this->setProperty($data, $name, $value, $useReflection);
        }
    }

    /**
     * Parses an array token, checks for a closing bracket at the end of the
     * token
     * @param string $token Token in the property
     * @param string $name Full property name
     * @return string Parsed name of the token
     * @throws ReflectionException when a invalid token has been provided
     */
    protected function parseArrayToken($token, $name) {
        $positionClose = strpos($token, ']');
        if ($positionClose === false) {
            throw new ReflectionException('Array ' . $token . ' opened but not closed in ' . $name);
        }

        if ($positionClose != (strlen($token) - 1)) {
            throw new ReflectionException('Array not closed before the end of the token in ' . $name);
        }

        return substr($token, 0, -1);
    }

    /**
     * Gets the possible arguments for a function/method call
     * @param string|array| \ride\library\reflection\Callback $callback Name of
     * the function or method in the class or a callback
     * @param mixed $class Class name for a static function, an instance for a
     * method call and null for a function call.
     * @return array Array with the name of the argument as key and an
     * instance of ReflectionParameter as value
     */
    public function getArguments($callback = null, $class = null) {
        if ($callback instanceof Callback) {
            $method = $callback->getMethod();
            $class = $callback->getClass();
        } elseif (is_array($callback)) {
            $callback = new Callback($callback);

            $method = $callback->getMethod();
            $class = $callback->getClass();
        } elseif ($class && $callback === null) {
            $method = '__construct';
        } else {
            $method = $callback;
        }

        try {
            if (!$class) {
                $reflectionFunction = new ReflectionFunction($method);
                $arguments = $reflectionFunction->getParameters();
            } else {
                if (!is_string($class)) {
                    $class = get_class($class);
                }

                $reflectionClass = $this->getReflectionClass($class);

                try {
                    $reflectionMethod = $reflectionClass->getMethod($method);
                    $arguments = $reflectionMethod->getParameters();
                } catch (\ReflectionException $exception) {
                    if ($method == '__construct' || $reflectionClass->hasMethod('__call')) {
                        $arguments = array();
                    } else {
                        throw $exception;
                    }
                }
            }
        } catch (Exception $exception) {
            throw $exception;
            throw new ReflectionException('Could not get the arguments for method ' . $method . ($class ? ' in ' . (!is_string($class) ? get_class($class) : $class) : ''), 0, $exception);
        }

        foreach ($arguments as $index => $argument) {
            $arguments[$argument->getName()] = $argument;
            unset($arguments[$index]);
        }

        return $arguments;
    }

    /**
     * Invokes the provided callback
     * @param mixed $callback Callback to invoke
     * @param array|null $arguments Arguments for the callback
     * @param boolean $isDynamic Set to true if the callback has arguments
     * which are not in the signature
     * @return mixed Return value of the callback
     */
    public function invoke($callback, array $arguments = null, $isDynamic = false) {
        $callback = new Callback($callback);
        if (!$callback->isCallable()) {
            throw new ReflectionException('Could not invoke ' . $callback);
        }

        if ($arguments === null) {
            $arguments = array();
        }

        $callbackArguments = $this->getArguments($callback);
        foreach ($callbackArguments as $name => $argument) {
            if (isset($arguments[$name])) {
                $callbackArguments[$name] = $arguments[$name];

                unset($arguments[$name]);
            } else {
                if ($argument->isOptional()) {
                    $callbackArguments[$name] = $argument->getDefaultValue();
                } else {
                    throw new ReflectionException('Could not invoke ' . $callback . ': mandatory parameter ' . $name . ' is not provided');
                }
            }
        }

        if ($arguments) {
            if ($isDynamic) {
                foreach ($arguments as $value) {
                    $callbackArguments[] = $value;
                }
            } else {
                $argumentNames = array();
                $argumentCount = 0;
                foreach ($arguments as $name => $value) {
                    $argumentNames[] = $name;
                    $argumentCount++;
                }

                $message = implode(', ', $argumentNames);
                if ($argumentCount == 1) {
                    $message .= ' is';
                } else {
                    $message .= ' are';
                }

                throw new ReflectionException('Could not invoke ' . $callback . ': ' . $message . ' not defined in the method signature');
            }
        }

        return $callback->invokeWithArguments($callbackArguments);
    }

    /**
     * Gets a reflection class
     * @param string $class Name of the class
     * @return \ReflectionClass
     */
    protected function getReflectionClass($class) {
        if (isset($this->classes[$class])) {
            return $this->classes[$class];
        }

        $reflectionClass = new ReflectionClass($class);

        $this->classes[$class] = $reflectionClass;

        return $reflectionClass;
    }

    /**
     * Gets a reflection property
     * @param mixed $instance Instance of the object
     * @param string $name Name of the property
     * @return \ReflectionProperty
     */
    protected function getReflectionProperty($instance, $name) {
        $class = get_class($instance);
        if (isset($this->properties[$class][$name])) {
            return $this->properties[$class][$name];
        }

        $reflectionProperty = new ReflectionProperty($class, $name);
        $reflectionProperty->setAccessible(true);

        $this->properties[$class][$name] = $reflectionProperty;

        return $reflectionProperty;
    }

}
