<?php

namespace pallo\library\reflection;

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

    public function testGetProperty() {
    	$result = $this->helper->getProperty($this, 'helper');

    	$this->assertTrue($result === $this->helper);

    	$result = $this->helper->getProperty($this, 'sme');

    	$this->assertTrue($result === 7);

    	$data = array(
    		'sme' => $this->sme,
    	);

    	$result = $this->helper->getProperty($data, 'sme');

    	$this->assertTrue($result === 7);
    }

    public function testGetPropertyReturnsDefaultValue() {
    	$default = 42;

    	$result = $this->helper->getProperty($this, 'unexistant', $default);

    	$this->assertTrue($result === $default);

    	$data = array();

    	$result = $this->helper->getProperty($data, 'unexistant', $default);

    	$this->assertTrue($result === $default);
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

    	$data = array();

    	$this->helper->setProperty($data, 'dummy', $value);

    	$this->assertTrue($data['dummy'] === $value);
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
    public function testGetArguments($expected, $class, $method) {
        $arguments = $this->helper->getArguments($class, $method);

        $this->assertEquals(array_keys($expected), array_keys($arguments));
    }

    public function providerGetArguments() {
        return array(
            array(array(), 'pallo\\library\\reflection\\ObjectFactory', '__construct'),
        	array(array(), new Callback(array('pallo\\library\\reflection\\ObjectFactory', '__construct')), null),
            array(array('class' => null, 'neededClass' => null, 'arguments' => null), 'pallo\\library\\reflection\\ObjectFactory', 'createObject'),
            array(array('expected' => null, 'class' => null, 'method' => null), $this, 'testGetArguments'),
            array(array('str' => null), null, 'strlen'),
        );
    }

    /**
     * @dataProvider providerGetArgumentsThrowsExceptionWhenInvalidClassProvided
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testGetArgumentsThrowsExceptionWhenInvalidClassProvided($class) {
		$this->helper->getArguments($class);
    }

    public function providerGetArgumentsThrowsExceptionWhenInvalidClassProvided() {
        return array(
            array(array()),
            array(null),
        );
    }

}