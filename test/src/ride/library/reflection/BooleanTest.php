<?php

namespace ride\library\reflection;

use \PHPUnit_Framework_TestCase;

class BooleanTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider providerGetBoolean
     */
    public function testGetBoolean($expected, $value) {
        $this->assertEquals($expected, Boolean::getBoolean($value));
    }

    public function providerGetBoolean() {
        return array(
    	    array(true, 'true'),
    	    array(true, 'yes'),
    	    array(true, 'y'),
    	    array(true, 'on'),
    	    array(true, 'TRUE'),
    	    array(true, 'YES'),
    	    array(true, 'Y'),
    	    array(true, 'ON'),
            array(true, '1'),
    	    array(false, 'false'),
    	    array(false, 'no'),
    	    array(false, 'n'),
    	    array(false, 'off'),
    	    array(false, '0'),
        );
    }

    /**
     * @dataProvider providerGetBooleanThrowsExceptionWhenInvalidValueProvided
     * @expectedException \InvalidArgumentException
     */
    public function testGetBooleanThrowsExceptionWhenInvalidValueProvided($value) {
        Boolean::getBoolean($value);
    }

    public function providerGetBooleanThrowsExceptionWhenInvalidValueProvided() {
        return array(
            array('test'),
            array(null),
            array($this),
        );
    }

}
