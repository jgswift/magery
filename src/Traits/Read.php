<?php
namespace Magery\Traits {
    use Magery\None;
    use Magery\Magic;
    /**
     * Read trait
     * @package Magery
     */
    trait Read {
        /**
         * Get magic
         * @param string $name
         * @return mixed
         */
        public function __get($name) {
            return (($value = Magic::read($this,$name)) instanceof None) 
                        ? isset($this->$name) ? $this->$name : null
                        : $value;
        }
    }
}
