<?php

namespace ride\library\reflection;

use \PHPUnit_Framework_TestCase;

class ReflectionHelperTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ReflectionHelper
     */
    private $helper;

    public $sme = 7;

    protected function setUp() {
        $this->helper = new ReflectionHelper();
    }

    public function testCreateObject() {
        $object = $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper');

        $this->assertNotNull($object, 'Result is null');
        $this->assertTrue($object instanceof ReflectionHelper, 'Result is not an instance of the requested class');

        // constructor without arguments
        $object = $this->helper->createObject('ride\\library\\reflection\\TestReflectionHelper3');

        $this->assertNotNull($object, 'Result is null');
        $this->assertTrue($object instanceof TestReflectionHelper3, 'Result is not an instance of the requested class');

        // test arguments
        $callback = $this->helper->createObject('ride\\library\\reflection\\Callback', array('callback' => 'strlen'));

        $this->assertNotNull($callback, 'Result is null');
        $this->assertTrue($callback instanceof Callback, 'Result is not an instance of the requested class');

        // test default arguments
        $exception = $this->helper->createObject('ride\\library\\reflection\\TestReflectionHelper');

        $this->assertNotNull($exception, 'Result is null');

        // test no constructor
        $exception = $this->helper->createObject('ride\\library\\reflection\\TestReflectionHelper2');

        $this->assertNotNull($exception, 'Result is null');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnInvalidClass() {
        $this->helper->createObject($this);
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenProvidedClassDoesNotExtendsNeededClass() {
        $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper', null, 'ride\\library\\Autoloader');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenProvidedClassDoesNotImplementNeededClass() {
        $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper', null, '\Iterator');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnNonExistingClass() {
        $this->helper->createObject('nonExistingClass');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnNonExistingNeededClass() {
        $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper', null, 'nonExistingClass');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionOnInvalidNeededClass() {
        $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper', null, $this);
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenRequiredConstructorParametersNotProvided() {
        $this->helper->createObject('ride\\library\\reflection\\Callback');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenConstructorParametersProvidedButConstructorDoesNotExist() {
        $this->helper->createObject('ride\\library\\reflection\\ReflectionHelper', array('test' => 'test'));
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateObjectThrowsExceptionWhenInvalidConstructorParametersProvided() {
        $this->helper->createObject('ride\\library\\reflection\\Callback', array('callback' => 'str_replace', 'dummy' => null));
    }

    public function testCreateData() {
        $dummy = 'dummy';
        $value = 'value';

        $arguments = array(
        	'value' => $value,
        );

        $instance = $this->helper->createData('ride\\library\\reflection\\TestReflectionHelper', $arguments);

        $this->assertTrue($instance instanceof TestReflectionHelper);
        $this->assertNull($instance->dummy);
        $this->assertEquals($value, $instance->value);

        $arguments['dummy'] = $dummy;

        $instance = $this->helper->createData('ride\\library\\reflection\\TestReflectionHelper', $arguments);

        $this->assertTrue($instance instanceof TestReflectionHelper);
        $this->assertEquals($dummy, $instance->dummy);
        $this->assertEquals($value, $instance->value);

        $instance = $this->helper->createData('ride\\library\\reflection\\TestReflectionHelper2', array());

        $this->assertTrue($instance instanceof TestReflectionHelper2);

        $callback = $this->helper->createData('ride\\library\\reflection\\Callback', array('callback' => array('ride\\library\\reflection\\ReflectionHelper', '__construct')));

        $this->assertTrue($callback instanceof Callback);
        $this->assertEquals($callback->__toString(), 'ride\\library\\reflection\\ReflectionHelper::__construct');
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testCreateDataThrowsExceptionWhenRequiredConstructorArgumentsNotProvided() {
        $this->helper->createData('ride\\library\\reflection\\Callback', array());
    }

    public function testGetProperty() {
        $result = $this->helper->getProperty($this, 'helper');

        $this->assertTrue($result === $this->helper);

        $result = $this->helper->getProperty($this, 'sme');

        $this->assertTrue($result === 7);

        $data = array(
            'sme' => $this->sme,
            'sub' => array(
                'sme' => $this->sme,
            ),
        );

        $result = $this->helper->getProperty($data, 'sme');
        $this->assertTrue($result === 7);

        $result = $this->helper->getProperty($data, 'sub[sme]');
        $this->assertTrue($result === 7);
    }

    public function testGetPropertyReturnsDefaultValue() {
        $default = 42;

        $result = $this->helper->getProperty($this, 'unexistant', $default);
        $this->assertTrue($result === $default);

        $data = array('key' => array('sub' => 'value'));

        $result = $this->helper->getProperty($data, 'unexistant', $default);
        $this->assertEquals($default, $result);

        $result = $this->helper->getProperty($data, 'key[unexistant]', $default);
        $this->assertEquals($default, $result);
    }

    /**
     * @dataProvider providerGetPropertyThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testGetPropertyThrowsExceptionWhenInvalidNameProvided($name) {
        $data = array('test' => 'value');
        $this->helper->getProperty($data, $name);
    }

    public function providerGetPropertyThrowsExceptionWhenInvalidNameProvided() {
        return array(
        	array(array()),
            array($this),
            array('[test]'),
            array('test[test[test]]'),
            array('test[test]test'),
        );
    }

    public function testSetProperty() {
        $property = 'value';
        $value = 7;

        $this->assertFalse(isset($this->$property));

        $this->helper->setProperty($this, $property, $value);

        $this->assertTrue(isset($this->$property));
        $this->assertTrue($this->$property === $value);

        unset($this->$property);

        $this->assertFalse(isset($this->$property));

        $this->helper->setProperty($this, 'dummy', $value);

        $this->assertTrue(isset($this->$property));
        $this->assertTrue($this->$property === $value);

        $data = array('sub' => array('value' => $value));

        $this->helper->setProperty($data, 'dummy', $value);
        $this->helper->setProperty($data, 'dummy2[sub]', $value);
        $this->helper->setProperty($data, 'sub[sub2][sub3]', $value);

        $this->assertTrue($data['dummy'] === $value);
        $this->assertTrue($data['dummy2']['sub'] === $value);
        $this->assertTrue($data['sub']['sub2']['sub3'] === $value);
    }

    /**
     * @dataProvider providerGetPropertyThrowsExceptionWhenInvalidNameProvided
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testSetPropertyThrowsExceptionWhenInvalidNameProvided($name) {
        $data = array('test' => 'value');
        $this->helper->setProperty($data, $name, 'value');
    }

    public function testSetProperties() {
        $value = 'value';
        $dummy = 9;

        $values = array(
            'sme' => $value,
            'dummy' => $dummy,
        );

        $this->assertTrue($this->sme === 7);
        $this->assertFalse(isset($this->value));

        $this->helper->setProperties($this, $values);

        $this->assertTrue($this->sme === $value);
        $this->assertTrue($this->value === $dummy);
    }

    public function getHelper() {
        return $this->helper;
    }

    public function setDummy($value) {
        $this->value = $value;
    }

    /**
     * @dataProvider providerGetArguments
     */
    public function testGetArguments($expected, $callback, $class) {
        $arguments = $this->helper->getArguments($callback, $class);

        $this->assertEquals(array_keys($expected), array_keys($arguments));
    }

    public function providerGetArguments() {
        return array(
            array(array('callback' => true), '__construct', 'ride\\library\\reflection\\Callback', '__construct'),
            array(array(), '__construct', 'ride\\library\\reflection\\TestObject'),
            array(array(), null, 'ride\\library\\reflection\\TestObject'),
            array(array('callback' => true), array('ride\\library\\reflection\\Callback', '__construct'), null),
            array(array('callback' => true), new Callback(array('ride\\library\\reflection\\Callback', '__construct')), null),
            array(array('class' => null, 'arguments' => null, 'neededInterface' => null), 'createObject', 'ride\\library\\reflection\\ReflectionHelper'),
            array(array('expected' => null, 'callback' => null, 'class' => null), 'testGetArguments', $this),
            array(array('str' => null), 'strlen', null),
        );
    }

    /**
     * @dataProvider providerGetArgumentsThrowsExceptionWhenInvalidCallbackProvided
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testGetArgumentsThrowsExceptionWhenInvalidCallbackProvided($callback, $class) {
        $this->helper->getArguments($callback, $class);
    }

    public function providerGetArgumentsThrowsExceptionWhenInvalidCallbackProvided() {
        return array(
            array(null, array()),
            array(null, null),
            array('unexistantMethod', null),
            array('unexistantMethod', $this),
            array('unexistantMethod', 'ride\\library\\reflection\\TestObject'),
        );
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

        $this->helper->invoke(array($this, 'method'), $arguments);

        $this->assertEquals($expects, $this->invokedArguments);
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenArgumentProvidedWhichAreNotInMethodSignature() {
        $arguments = array(
            'argument1' => 'value1',
            'argument2' => 'value2',
            'argument3' => 'value3',
        );

        $this->helper->invoke(array($this, 'method'), $arguments);
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenArgumentsProvidedWhichAreNotInMethodSignature() {
        $arguments = array(
            'argument1' => 'value1',
            'argument2' => 'value2',
            'argument3' => 'value3',
            'argument4' => 'value4',
        );

        $this->helper->invoke(array($this, 'method'), $arguments);
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

        $this->helper->invoke(array($this, 'method'), $arguments, true);

        $this->assertEquals($expects, $this->invokedArguments);
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenCallbackNotCallable() {
        $this->helper->invoke(array($this, 'unexistantMethod'));
    }

    /**
     * @expectedException ride\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenRequiredArgumentNotProvided() {
        $this->helper->invoke(array($this, 'method'));
    }

}

class TestObject {

}

class TestReflectionHelper extends ReflectionHelper {

    public $dummy;

    public $value;

    public function __construct($dummy = null) {
        $this->dummy = $dummy;
    }

    public function setValue($value) {
        $this->value = $value;
    }

}

class TestReflectionHelper2 extends ReflectionHelper {

}

class TestReflectionHelper3 extends ReflectionHelper {

    public function __construct() {

    }

}
