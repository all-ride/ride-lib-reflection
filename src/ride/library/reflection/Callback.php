<?php

namespace ride\library\reflection;

use ride\library\reflection\exception\ReflectionException;

/**
 * Callback object for dynamic method invokation
 */
class Callback {

    /**
     * Callback to wrap around
     * @var string|array
     */
    protected $callback;

    /**
     * Instance of a class or a class name for a static call
     * @var mixed
     */
    protected $class;

    /**
     * Name of a method in the provided class or a function name
     * @var string
     */
    protected $method;

    /**
     * Constructs a new callback
     * @param string|array|Callback $callback Callback to wrap
     * @return null
     * @throws Exception when the provided callback is invalid
     */
    public function __construct($callback) {
        $this->setCallback($callback);
    }

    /**
     * Gets a string representation of this callback
     * @return string
     */
    public function __toString() {
        if (!$this->class) {
            return $this->method;
        }

        if (is_string($this->class)) {
            return $this->class . '::' . $this->method;
        }

        return get_class($this->class) . '->' . $this->method;
    }

    /**
     * Sets the callback
     * @param string|array|Callback $callback A string for a function call, an
     * array with as first argument the class name (for static methods) or
     * instance and as a second argument the method name. Another instance of
     * Callback is also possible.
     * @return null
     * @throws ride\library\reflection\exception\ReflectionException when an
     * invalid callback has been provided
     */
    public function setCallback($callback) {
        if ($callback instanceof self) {
            // callback is already an instance of Callback, copy it's variables
            $this->callback = $callback->callback;
            $this->class = $callback->class;
            $this->method = $callback->method;

            return;
        }

        if (is_string($callback) && $callback) {
            // callback is a string: a global function call
            $this->callback = $callback;
            $this->class = null;
            $this->method = $callback;

            return;
        }

        // callback is an array with a class name or class instance as first
        // element and the method as the second element
        if (!is_array($callback)) {
            throw new ReflectionException('Could not set callback: callback is not a string or an array');
        }
        if (count($callback) != 2) {
            throw new ReflectionException('Could not set callback: callback array should have only 2 elements');
        }
        if (!isset($callback[0]) || !$callback[0]) {
            throw new ReflectionException('Could not set callback: callback array should have an element 0 containing the class name or a class instance');
        }
        if (!isset($callback[1]) || !$callback[1]) {
            throw new ReflectionException('Could not set callback: callback array should have an element 1 containing the method name');
        }

        $this->class = $callback[0];
        $this->method = $callback[1];

        if (!is_string($this->class) && !is_object($this->class)) {
            throw new ReflectionException('Could not set callback: class parameter is invalid');
        }

        if (!is_string($this->method) || !$this->method) {
            throw new ReflectionException('Could not set callback: method parameter is invalid or empty');
        }

        $this->callback = $callback;
    }

    /**
     * Gets the instance of the class or a class name in case of a static call
     * @return mixed
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * Gets the method in the class or if no class is set, a global function
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Checks if this callback is callable
     * @return boolean True if the callback is callable, false otherwise
     */
    public function isCallable() {
        return is_callable($this->callback);
    }

    /**
     * Invokes the callback. All arguments passed to this method will be passed
     * on to the callback
     * @return mixed Result of the callback
     */
    public function invoke() {
        $arguments = func_get_args();

        return $this->invokeWithArguments($arguments);
    }

    /**
     * Invokes the callback with an array of arguments
     * @param array $arguments Arguments for the callback
     * @return mixed Result of the callback
     * @throws ride\library\reflection\exception\ReflectionException when the
     * callback is not callable
     */
    public function invokeWithArguments(array $arguments) {
        if (!$this->isCallable()) {
            throw new ReflectionException('Could not invoke ' . $this . ': callback is not callable');
        }

        return call_user_func_array($this->callback, $arguments);
    }

}