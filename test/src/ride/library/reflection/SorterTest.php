<?php

namespace ride\library\reflection;

use \PHPUnit_Framework_TestCase;

class SorterTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider providerSort
     */
    public function testSort($expected, $value, $options) {
        $sorter = new Sorter(new ReflectionHelper(), $options);

        $this->assertEquals($expected, $sorter->sort($value));
    }

    public function providerSort() {
        return array(
    	    array(
                array(
                    'A' => array('name' => 'A'),
                    'B' => array('name' => 'B'),
                    'C' => array('name' => 'C'),
                ),
                array(
                    'C' => array('name' => 'C'),
                    'A' => array('name' => 'A'),
                    'B' => array('name' => 'B'),
                ),
                array('name' => true),
            ),
            array(
                array(
                    1 => array(
                        'name' => 'Jane',
                        'age' => 18,
                    ),
                    3 => array(
                        'name' => 'Tom',
                        'age' => 21,
                    ),
                    0 => array(
                        'name' => 'John',
                        'age' => 21,
                    ),
                    2 => array(
                        'name' => 'Mark',
                        'age' => 30,
                    ),
                ),
                array(
                    0 => array(
                        'name' => 'John',
                        'age' => 21,
                    ),
                    1 => array(
                        'name' => 'Jane',
                        'age' => 18,
                    ),
                    2 => array(
                        'name' => 'Mark',
                        'age' => 30,
                    ),
                    3 => array(
                        'name' => 'Tom',
                        'age' => 21,
                    ),
                ),
                array('age' => true, 'name' => false),
            ),
        );
    }

}
