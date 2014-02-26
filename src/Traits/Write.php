<?php
namespace Magery\Traits {
    use Magery\Magic;
    /**
     * Write trait
     * @package Magery
     */
    trait Write {
        /**
         * Set trait
         * @param string $name
         * @param mixed $value
         * @return mixed
         */
        public function __set($name,$value) {
            return Magic::write($this,$name,$value);
        }
    }
}
