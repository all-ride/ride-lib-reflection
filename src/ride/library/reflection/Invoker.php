<?php

namespace ride\library\reflection;

/**
 * Interface to invoke callbacks
 */
interface Invoker {

    /**
     * Invokes the provided callback
     * @param mixed $callback Callback to invoke
     * @param array|null $arguments Arguments for the callback
     * @param boolean $isDynamic Set to true if the callback has arguments
     * which are not in the signature
     * @return mixed Return value of the callback
     */
    public function invoke($callback, array $arguments = null, $isDynamic = false);

}