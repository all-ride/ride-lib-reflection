<?php

namespace pallo\library\reflection;

use \PHPUnit_Framework_TestCase;

class GenericInvokerTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ObjectFactory
     */
    private $invoker;

    protected function setUp() {
        $this->invoker = new GenericInvoker();
    }

    public function testSetAndGetReflectionHelper() {
    	$helper = $this->invoker->getReflectionHelper();

    	$this->assertTrue($helper instanceof ReflectionHelper);
    }

    public function method($argument1, $argument2 = 'val') {
    	$this->invokedArguments = func_get_args();
    }

    public function testInvoke() {
		$this->invokedArguments = array();

		$arguments = array(
			'argument2' => 'value2',
			'argument1' => 'value1',
		);

		$expects = array(
			'value1',
			'value2',
		);

    	$this->invoker->invoke(array($this, 'method'), $arguments);

    	$this->assertEquals($expects, $this->invokedArguments);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenArgumentProvidedWhichAreNotInMethodSignature() {
		$arguments = array(
			'argument1' => 'value1',
			'argument2' => 'value2',
			'argument3' => 'value3',
		);

    	$this->invoker->invoke(array($this, 'method'), $arguments);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenArgumentsProvidedWhichAreNotInMethodSignature() {
		$arguments = array(
			'argument1' => 'value1',
			'argument2' => 'value2',
			'argument3' => 'value3',
			'argument4' => 'value4',
		);

    	$this->invoker->invoke(array($this, 'method'), $arguments);
    }

    public function testInvokeWithDynamicArguments() {
		$this->invokedArguments = array();

    	$arguments = array(
    		'argument1' => 'value1',
    		'argument3' => 'value3',
    	);

    	$expects = array(
    		'value1',
    		'val',
    		'value3',
    	);

    	$this->invoker->invoke(array($this, 'method'), $arguments, true);

    	$this->assertEquals($expects, $this->invokedArguments);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenCallbackNotCallable() {
    	$this->invoker->invoke(array($this, 'unexistantMethod'));
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenRequiredArgumentNotProvided() {
    	$this->invoker->invoke(array($this, 'method'));
    }

}