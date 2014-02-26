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
    }
}
