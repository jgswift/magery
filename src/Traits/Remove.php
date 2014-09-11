<?php
namespace Magery\Traits {
    use Magery\Magic;
    /**
     * Remove trait
     * @package Magery
     */
    trait Remove {
        /**
         * Unset magic
         * @param string $name
         */
        public function __unset($name) {
            Magic::remove($this,$name);
        }
        
        /**
         * Helper shortcut method
         * @param string $name
         * @param \callable $callable
         */
        protected function remove($name, callable $callable) {
            $this->magery('remove', $name, $callable);
        }
    }
}
