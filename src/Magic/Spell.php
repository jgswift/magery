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
                return self::triggerCache($object, $name, $value, $eventData);
            }
            
            return self::triggerDirect($object, $value, $eventData);
        }
        
        /**
         * Cached triggering
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @param array $eventData
         * @return null
         * @throws Exception
         */
        private static function triggerCache($object, $name, $value, &$eventData) {
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
            
            $response = call_user_func_array($callable,$args);

            if($eventData['cache'] && 
                is_null($response)) {
                throw new Magery\Exception('Event registered on read of variable "' . $name . '" does not return a cacheable response - cannot be null');
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