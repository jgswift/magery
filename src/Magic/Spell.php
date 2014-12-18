<?php
namespace Magery\Magic {
    use Magery;
    
    class Spell {
        /**
         * Helper method that performs trigger
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @param array $eventData
         * @param boolean $cache
         * @return mixed
         */
        public static function trigger($object, $name, $value, &$eventData, $cache = false) {
            if($cache) {
                return self::notifyCache($object, $name, $value, $eventData);
            }
            
            return self::triggerDirect($object, $value, $eventData);
        }
        
        /**
         * ome preliminary checks to increase fault tolerance
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @param array $eventData
         * @return null
         */
        private static function notifyCache($object, $name, $value, &$eventData) {
            if($eventData['cache'] && 
                isset($eventData['response'])) {
                return $eventData['response'];
            }

            $callable = $eventData['callable'];
            if($callable instanceof \Closure) {
                $callable->bindTo($object,$object);
            }

            $args = [];
            if(is_array($value)) {
                $args = $value;
            } 
            
            return self::triggerCache($callable, $args, $name, $eventData);
        }
        
        /**
         * 
         * @param mixed $callable
         * @param array $args
         * @param string $name
         * @param array $eventData
         * @return mixed
         * @throws Magery\Exception
         */
        private static function triggerCache($callable, array $args, $name, &$eventData) {
            $response = call_user_func_array($callable,$args);

            if($eventData['cache'] && 
                is_null($response)) {
                throw new Magery\Exception('Event registered on variable "' . $name . '" does not return a cacheable response - cannot be null');
            }

            if(isset($response)) {
                if ($eventData['cache']) {
                    $eventData['response'] = $response;
                    return $eventData['response'];
                }

                return $response;
            }
            
            return null;
        }
        
        /**
         * Noncached triggering
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @param array $eventData
         */
        private static function triggerDirect($object, $value, $eventData) {
            $callable = $eventData['callable'];
            if($callable instanceof \Closure) {
                $callable->bindTo($object,$object);
            }
            $callable($value);
        }
    }
}