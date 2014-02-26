<?php
namespace Magery {
    /**
     * Object trait
     * @package Magery
     */
    trait Object {
        /**
         * Registers spell event with global magic manager
         * @param string $spell
         * @param mixed $variables
         * @param callable $callable
         * @param boolean $cacheResponse
         */
        protected function magery($spell,$variables,callable $callable,$cacheResponse=false) {
            Magic::register($this,$spell,$variables,$callable,$cacheResponse);
        }
    }
}
