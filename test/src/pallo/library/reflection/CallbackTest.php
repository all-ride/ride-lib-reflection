<?php

namespace pallo\library\reflection;

use \PHPUnit_Framework_TestCase;

class CallbackTest extends PHPUnit_Framework_TestCase {

    private $invoked = false;

    /**
     * @dataProvider providerConstruct
     */
    public function testConstruct($expected, $callback) {
        $callback = new Callback($callback);

        $this->assertEquals($expected, (string) $callback);
    }

    public function providerConstruct() {
        return array(
            array('str_replace', 'str_replace'),
            array('pallo\\library\\Url::getBaseUrl', array('pallo\\library\\Url', 'getBaseUrl')),
            array('pallo\\library\\reflection\\CallbackTest->testConstruct', array($this, 'testConstruct')),
            array('pallo\\library\\reflection\\CallbackTest->testConstruct', new Callback(array($this, 'testConstruct'))),
        );
    }

    /**
     * @dataProvider providerConstructThrowsExceptionWhenInvalidCallbackPassed
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testConstructThrowsExceptionWhenInvalidCallbackPassed($callback) {
		new Callback($callback);
    }

    public function providerConstructThrowsExceptionWhenInvalidCallbackPassed() {
        return array(
            array(''),
            array(array('testClass', 'testFunction', '1 more')),
            array(array('object' => 'testClass', 'function' => 'testFunction')),
            array(array('test', '')),
            array(array('', 'test')),
            array(array('', $this)),
            array(array(array('test'), 'test')),
            array(array('test', array('test'))),
            array($this),
        );
    }

    public function testInvoke() {
        $this->invoked = false;

        $callback = new Callback(array($this, 'invoke'));
        $callback->invoke();

        $this->assertEquals(true, $this->invoked);
    }

    public function testInvokeReturnsValue() {
        $value = 'value';

        $callback = new Callback(array($this, 'invoke'));
        $result = $callback->invoke($value);

        $this->assertEquals($value, $result);
    }

    public function testInvokeWithArguments() {
        $this->invoked = false;

        $callback = new Callback(array($this, 'invoke'));
        $callback->invoke('test', true);

        $this->assertEquals(array('test', true), $this->invoked);
    }

    /**
     * @dataProvider providerInvokeThrowsExceptionWhenUnableToInvokeCallback
     * @expectedException pallo\library\reflection\exception\ReflectionException
     */
    public function testInvokeThrowsExceptionWhenUnableToInvokeCallback($callback) {
        $callback = new Callback($callback);
		$callback->invoke();
    }

    public function providerInvokeThrowsExceptionWhenUnableToInvokeCallback() {
        return array(
            array('unexistingFunction'),
            array(array('unexistingClass', 'function')),
            array(array($this, 'unexistingFunction')),
        );
    }

    public function invoke() {
        $args = func_get_args();
        if (empty($args)) {
            $args = true;
        } elseif (count($args) == 1) {
            $args = array_shift($args);
        }

        return $this->invoked = $args;
    }

}