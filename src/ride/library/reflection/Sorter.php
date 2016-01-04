<?php

namespace ride\library\reflection;

/**
 * Sorter of data structures being arrays or objects
 */
class Sorter {

    /**
     * Instance of a reflection helper to retrieve the properties of the data
     * structures
     * @var ReflectionHelper
     */
    protected $reflectionHelper;

    /**
     * Array with the property name to sort on as key and a boolean as value:
     * true for ascending, false for descending
     * @var array
     */
    protected $sortProperties;

    /**
     * Constructs a new sorter
     * @param ReflectionHelper $reflectionHelper Instance of a reflection helper
     * @param array $sortProperties Array with the property name to sort on as
     * key and a boolean as value: true for ascending, false for descending
     * @return null
     */
    public function __construct(ReflectionHelper $reflectionHelper, array $sortProperties) {
        $this->reflectionHelper = $reflectionHelper;
        $this->sortProperties = $sortProperties;
    }

    /**
     * Sorts the provided data on the sort properties of this sorter
     * @param array $data Data to sort
     * @return array Sorted data
     */
    public function sort(array $data) {
        if ($this->sortProperties) {
            usort($data, array($this, 'performCompare'));
        }

        return $data;
    }

    /**
     * Compares the provided data structures on the sort properties
     * @param mixed $data1 First data structure
     * @param mixed $data2 Second data structure
     * @return integer 1 when the first data structure is greater, -1 when the
     * second data structure is greater and 0 when they are equal
     */
    protected function performCompare($data1, $data2) {
        foreach ($this->sortProperties as $sortField => $sortDirection) {
            $value1 = $this->reflectionHelper->getProperty($data1, $sortField);
            $value2 = $this->reflectionHelper->getProperty($data2, $sortField);

            if (is_numeric($value1) && is_numeric($value2)) {
                if ($value1 > $value2) {
                    $result = 1;
                } elseif ($value < $value2) {
                    $result = -1;
                } else {
                    $result = 0;
                }
            } elseif ((is_string($value1) || is_object($value1)) && (is_string($value2) || is_object($value2))) {
                $result = strcmp((string) $value1, (string) $value2);
            } else {
                $result = 0;
            }

            if (!$sortDirection) {
                $result *= -1;
            }

            if ($result != 0) {
                return $result;
            }
        }

        return 0;
    }

}
