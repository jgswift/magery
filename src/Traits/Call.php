<?php
namespace Magery\Traits {
    use Magery\Magic;
    /**
     * Call trait
     * @package Magery
     */
    trait Call {
        /**
         * Call magic
         * @param string $name
         * @param array $arguments
         * @return mixed
         */
        function __call($name,$arguments) {
            return Magic::call($this,$name,$arguments);
        }
        
        /**
         * Helper shortcut method
         * @param string $name
         * @param \callable $callable
         */
        protected function call($name, callable $callable) {
            $this->magery('call', $name, $callable);
        }
    }
}
