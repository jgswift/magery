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
        
        /**
         * Helper method for magery
         * @param type $variables
         * @param type $callable
         * @param type $cacheResponse
         */
        protected function read($variables, callable $callable, $cacheResponse = false) {
            $this->magery('read', $variables, $callable, $cacheResponse);
        }
    }
}
