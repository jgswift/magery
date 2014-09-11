<?php
namespace Magery {
    /**
     * Object trait
     * @package Magery
     */
    trait Object {
        /**
         * Registers spell event with global magic manager
         * @param string $event
         * @param mixed $variables
         * @param callable $callable
         * @param boolean $cacheResponse
         */
        protected function magery($event, $variables, callable $callable, $cacheResponse=false) {
            if(!is_string($event)) {
                throw new \InvalidArgumentException;
            }
            
            Magic::register($this, $event, $variables, $callable, $cacheResponse);
        }
    }
}
