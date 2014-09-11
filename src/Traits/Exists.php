<?php
namespace Magery\Traits {
    use Magery\Magic;
    /**
     * Exists trait
     * @package Magery
     */
    trait Exists {
        /**
         * Isset magic
         * @param string $name
         * @return boolean
         */
        function __isset($name) {
            return (bool)Magic::exists($this,$name);
        }
        
        /**
         * Helper shortcut method
         * @param string $name
         * @param \callable $callable
         */
        function exists($name, callable $callable) {
            $this->magery('exists', $name, $callable);
        }
    }
}
