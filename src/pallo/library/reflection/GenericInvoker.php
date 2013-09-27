<?php

namespace pallo\library\reflection;

use pallo\library\reflection\exception\ReflectionException;

/**
 * Generic implementation of the Invoker interface
 */
class GenericInvoker {

	/**
	 * Sets the reflection helper
	 * @param ReflectionHelper $reflectionHelper
	 * @return null
	 */
	public function __construct(ReflectionHelper $reflectionHelper = null) {
		if (!$reflectionHelper) {
			$reflectionHelper = new ReflectionHelper();
		}

		$this->reflectionHelper = $reflectionHelper;
	}

	/**
	 * Gets the reflection helper
	 * @return ReflectionHelper
	 */
	public function getReflectionHelper() {
		return $this->reflectionHelper;
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

		$callbackArguments = $this->reflectionHelper->getArguments($callback);
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

		return $callback->invokeWithArrayArguments($callbackArguments);
	}

}