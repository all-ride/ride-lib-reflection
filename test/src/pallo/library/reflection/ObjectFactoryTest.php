<?php

namespace pallo\library\reflection;

use \PHPUnit_Framework_TestCase;

class ObjectFactoryTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ObjectFactory
     */
    private $factory;

    protected function setUp() {
        $this->factory = new ObjectFactory();
    }

    public function testSetAndGetReflectionHelper() {
    	$helper = $this->factory->getReflectionHelper();

    	$this->assertTrue($helper instanceof ReflectionHelper);

    	$helper2 = new ReflectionHelper();

    	$this->factory->setReflectionHelper($helper2);

    	$result = $this->factory->getReflectionHelper();

    	$this->assertTrue($result !== $helper);
    	$this->assertTrue($result === $helper2);
    }

    public function testCreateObject() {
        $object = $this->factory->createObject('pallo\\library\\reflection\\ObjectFactory');

        $this->assertNotNull($object, 'Result is null');
        $this->assertTrue($object instanceof ObjectFactory, 'Result is not an instance of the requested class');

        // test arguments
        $callback = $this->factory->createObject('pallo\\library\\reflection\\Callback', null, array('callback' => 'strlen'));

        $this->assertNotNull($callback, 'Result is null');
        $this->assertTrue($callback instanceof Callback, 'Result is not an instance of the requested class');

        // test default arguments
        $exception = $this->factory->createObject('pallo\\library\\reflection\\TestReflectionHelper');

        $this->assertNotNull($exception, 'Result is null');

        // test no constructor
        $exception = $this->factory->createObject('pallo\\library\\reflection\\TestReflectionHelper2');

        $this->assertNotNull($exception, 'Result is null');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnInvalidClass() {
		$this->factory->createObject($this);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenProvidedClassDoesNotExtendsNeededClass() {
		$this->factory->createObject('pallo\\library\\reflection\\ObjectFactory', 'pallo\\library\\Autoloader');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenProvidedClassDoesNotImplementNeededClass() {
		$this->factory->createObject('pallo\\library\\reflection\\ObjectFactory', '\Iterator');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnNonExistingClass() {
		$this->factory->createObject('nonExistingClass');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnNonExistingNeededClass() {
		$this->factory->createObject('pallo\\library\\reflection\\ObjectFactory', 'nonExistingClass');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnInvalidNeededClass() {
		$this->factory->createObject('pallo\\library\\reflection\\ObjectFactory', $this);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenRequiredConstructorParametersNotProvided() {
		$this->factory->createObject('pallo\\library\\reflection\\Callback');
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenConstructorParametersProvidedButConstructorDoesNotExist() {
		$this->factory->createObject('pallo\\library\\reflection\\ReflectionHelper', null, array('test' => 'test'));
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenInvalidConstructorParametersProvided() {
		$this->factory->createObject('pallo\\library\\reflection\\Callback', null, array('callback' => 'str_replace', 'dummy' => null));
    }

    public function testCreateData() {
		$factory = $this->factory->createData('pallo\\library\\reflection\\ObjectFactory', array('reflectionHelper' => new TestReflectionHelper()));

		$this->assertTrue($factory instanceof ObjectFactory);
		$this->assertTrue($factory->getReflectionHelper() instanceof TestReflectionHelper);

		$callback = $this->factory->createData('pallo\\library\\reflection\\Callback', array('callback' => array('pallo\\library\\reflection\\ObjectFactory', '__construct')));

		$this->assertTrue($callback instanceof Callback);
		$this->assertEquals($callback->__toString(), 'pallo\\library\\reflection\\ObjectFactory::__construct');

		$helper = $this->factory->createData('pallo\\library\\reflection\\TestReflectionHelper', array());

		$this->assertTrue($helper instanceof TestReflectionHelper);
    }

    /**
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testCreateDataThrowsExceptionWhenRequiredConstructorArgumentsNotProvided() {
		$this->factory->createData('pallo\\library\\reflection\\Callback', array());
    }

}

class TestReflectionHelper extends ReflectionHelper {

	public function __construct($dummy = null) {

	}

}

class TestReflectionHelper2 extends ReflectionHelper {

}