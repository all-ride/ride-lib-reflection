<?php

namespace ride\library\reflection;

use \InvalidArgumentException;

/**
 * Boolean library
 *
 * Based on CommandLine utility by Patrick Fisher <patrick@pwisher.com>
 * @see https://github.com/pwfisher/CommandLine.php
 */
class Boolean {

    /**
     * Array with values considered as boolean
     * @var array
     */
    private static $booleanValues = array(
    	'true' => true,
    	'yes' => true,
    	'y' => true,
    	'on' => true,
        '1' => true,
    	'false' => false,
    	'no' => false,
    	'n' => false,
    	'off' => false,
    	'0' => false
    );

    /**
     * Gets the boolean value
     *
     * Values considered as boolean:
     * <ul>
     * <li>true, false</li>
     * <li>yes, no</li>
     * <li>y, n</li>
     * <li>on, off</li>
     * <li>1, 0</li>
     * </ul>
     * @param mixed $value
     * @return boolean
     * @throws InvalidArgumentException when the provided value is not a valid
     * boolean value
     */
    public static function getBoolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value) && ($value == 1 || $value == 0)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            if (isset(self::$booleanValues[$value])) {
                return self::$booleanValues[$value];
            }
        } elseif (is_object($value)) {
            $value = get_class($value);
        } else {
            $value = 'Array';
        }

        throw new InvalidArgumentException($value . ' is not a boolean value');
    }

}
