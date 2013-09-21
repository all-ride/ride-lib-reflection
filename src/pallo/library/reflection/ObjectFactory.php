<?php

namespace pallo\library\reflection;

use pallo\library\reflection\exception\ReflectionException;

use \ReflectionClass;
use \Exception;

/**
 * Create objects on the fly by their class name and optional class interface
 * (implements or extends)
 */
class ObjectFactory {

    /**
     * Helper for reflection
     * @var ReflectionHelper
     */
    protected $reflectionHelper;

    /**
     * Constructs a new object factory
     * @return null
     */
    public function __construct() {
        $this->reflectionHelper = null;
    }

    /**
     * Sets the reflection helper
     * @param ReflectionHelper $reflectionHelper
     * @return null
     */
    public function setReflectionHelper(ReflectionHelper $reflectionHelper = null) {
        $this->reflectionHelper = $reflectionHelper;
    }

    /**
     * Gets the reflection helper
     * @return ReflectionHelper
     */
    public function getReflectionHelper() {
        if (!$this->reflectionHelper) {
            $this->reflectionHelper = new ReflectionHelper();
        }

        return $this->reflectionHelper;
    }

    /**
     * Creates an instance of the provided class
     * @param string $class Full name of the class
     * @param string|null $neededClass Full name of the interface or parent class
     * @param array|null $arguments Named arguments for the constructor
     * @return mixed New instance of the requested class
     * @throws Exception when an invalid argument is provided
     * @throws Exception when the class does not exists
     * @throws Exception when the class does not implement/extend the provided
     * @throws Exception when a mandatory construct parameter is missing in the
     * provided parameters
     * needed class
     */
    public function createObject($class, $neededClass = null, array $arguments = null) {
    	// validate the class
        if (!is_string($class) || !$class) {
            throw new ReflectionException('Could not create object: provided class is empty or not a string');
        }

        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (Exception $e) {
            throw new ReflectionException('Could not create object: class ' . $class . ' not found', 0, $e);
        }

        // validate class inheritance with the needed class
        if ($neededClass && $class != $neededClass) {
            if (!is_string($neededClass)) {
                throw new ReflectionException('Could not create object: provided needed class is empty or not a string');
            }

            try {
                $neededReflectionClass = new ReflectionClass($neededClass);
            } catch (Exception $e) {
                throw new ReflectionException('Could not create object: needed class ' . $neededClass . ' not found', 0, $e);
            }

            if ($neededReflectionClass->isInterface() && !$reflectionClass->implementsInterface($neededClass)) {
                throw new ReflectionException('Could not create object: ' . $class . ' does not implement ' . $neededClass);
            } elseif (!$reflectionClass->isSubclassOf($neededClass)) {
                throw new ReflectionException('Could not create object: ' . $class . ' does not extend ' . $neededClass);
            }
        }

        // validate the constructor parameters
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor && $arguments) {
        	throw new ReflectionException('Could not create ' . $class . ': constructor parameters provided while there is no constructor');
        }

        $constructorArguments = $constructor->getParameters();
        if (!$constructorArguments && !$arguments) {
	        // no parameters, create and return the object instance
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
     * Creates a data instance
     * @param string $class Full name of the data class
     * @param array $values Values for the data
     * @return mixed Instance of the data object
     * @throws Exception when the data class could not be created
     * @throws Exception when a mandatory constructor parameter is missing in
     * the provided properties
     */
    public function createData($class, array $properties) {
        $reflectionHelper = $this->getReflectionHelper();

		// gather the constructor parameters
        $arguments = $reflectionHelper->getArguments($class);
        foreach ($arguments as $name => $argument) {
            if (isset($properties[$name])) {
                $arguments[$name] = $properties[$name];

                unset($properties[$name]);
            } elseif (!$argument->isOptional()) {
            	throw new ReflectionException('Could not ' . $class . ': mandatory construct parameter ' . $name . ' is not set in the provided properties');
            } else {
                $arguments[$name] = $argument->getDefaultValue();
            }
        }

        // create the data instance
        $data = $this->createObject($class, null, $arguments);

		// set the remaining properties
        foreach ($properties as $name => $value) {
            $reflectionHelper->setProperty($data, $name, $value);
        }

        return $data;
    }

}