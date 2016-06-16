# Ride: Reflection Library

Reflection helper library of the PHP Ride framework.

## Boolean

A helper to obtain a boolean value from various string formats:

```php
<?php

use ride\library\reflection\Boolean;

$bool = Boolean::getBoolean('yes'); // true
$bool = Boolean::getBoolean('off'); // false
```

## Invoker

An interface to invoke dynamic callbacks.
It can be used by eg an event manager, a controller dispatcher, ...

The ReflectionHelper class implements this interface to offer a generic implementation out of the box.

## ReflectionHelper

The reflection helper offers an easy interface for dynamic programming:

* It obtains the arguments of any callback as a named array
* It creates objects or data containers with named arguments.
* It gets and sets values from and to generic data containers. These data containers can be arrays or object instances.

Check the following code sample:

```php
<?php

use ride\library\reflection\ReflectionHelper;

$reflectionHelper = new ReflectionHelper();

// create an object
$date = $reflectionHelper->createObject('DateTime', array('time' => '6 July 1983'));

// create an object for a specific interface
$decorator = $reflectionHelper->createObject('ride\\library\\reflection\\ReflectionHelper', null, 'ride\\library\\reflection\\Invoker');

// get and set properties
$data = array();

$reflectionHelper->setProperty($data, 'property', '1');
$reflectionHelper->setProperty($data, 'sub[property]', '2');
// $data = array(
//     'property' => '1'
//     'sub' => array(
//         'property' => '2',
//     ),
// );

$result = $reflectionHelper->getProperty($data, 'property'); // 1
$result = $reflectionHelper->getProperty($data, 'sub[property]'); // 2
$result = $reflectionHelper->getProperty($data, 'sub[unexistant]'); // null
$result = $reflectionHelper->getProperty($data, 'sub[unexistant]', 'default'); // default

// what if we work with objects     
$data = new DateTime();

// will call $data->setTimestamp('value');
$reflectionHelper->setProperty($data, 'timestamp', time()); 

// will set $data->unexistant to 'value'
$reflectionHelper->setProperty($data, 'unexistant', 'value'); 

// will check $data->getUnexistant2() and $data->unexistant2 before return 'default'
$result = $reflectionHelper->getProperty($data, 'unexistant2', 'default'); 

// retrieve callback arguments
$arguments = $reflectionHelper->getArguments('strpos');
$arguments = $reflectionHelper->getArguments('safeString', 'ride\library\String');
$arguments = $reflectionHelper->getArguments('safeString', new ride\library\String());
$arguments = $reflectionHelper->getArguments(array($data, 'safeString');
// $arguments = array(
//     'replacement' => ReflectionParameter[...],
//     'lower' => ReflectionParameter[...],
// );     

// invoke a callback
$callback = array($reflectionHelper, 'createObject');
$arguments = array(
    'arguments' => array('time' => 'now'),
    'class' => 'DateTime',
);

$date = $reflectionHelper->invoke($callback, $arguments);
```

## Sorter

Implementation to sort data containers, being arrays or objects, on different properties simultaneously.

```
<?php

use ride\library\reflection\ReflectionHelper;
use ride\library\reflection\Sorter;

// generate some data containers
$entry1 = array(
    'name' => 'John',
    'age' => 21,
);
$entry2 = array(
    'name' => 'Jane',
    'age' => 18,
);
$entry3 = array(
    'name' => 'Mark',
    'age' => 30,
);
$entry4 = array(
    'name' => 'Tom',
    'age' => 21,
);

$entries = array(
    $entry1,
    $entry2,
    $entry3,
    $entry4,
);

// create a sorter age ASC, name ASC
$sorter = new Sorter(new ReflectionHelper(), array('age' => true, 'name' => true));

// sort the entries
print_r($sorter->sort($entries));
/*
array(
    1 => array(
        'name' => 'Jane',
        'age' => 18,
    ),
    0 => array(
        'name' => 'John',
        'age' => 21,
    ),
    3 => array(
        'name' => 'Tom',
        'age' => 21,
    ),
    2 => array(
        'name' => 'Mark',
        'age' => 30,
    ),
);
*/
```
