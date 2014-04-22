<?php
namespace Magery {
    /**
    * Magic handling class
    * Class Magic
    * @package Magery
    */
    class Magic {
        /**
         * Stores magic operations
         * @var array
         */
        private static $objects = [];
        
        /**
         * Stores individual object variables
         * @var array
         */
        private static $variables = [];
        
        /**
         * Helper function to return standard object ID
         * @param mixed $object
         * @return string
         */
        private static function id($object) {
            static $hashes;

            if (!isset($hashes)) $hashes = array();

            // find existing instance
            foreach ($hashes as $hash => $o) {
                if ($object === $o) return $hash;
            }

            $hash = md5(uniqid());
            while (array_key_exists($hash, $hashes)) {
                $hash = md5(uniqid());
            }
            
            self::$objects[$hash] = [];

            $hashes[$hash] = $object;
            return $hash;
        }
        
        /**
         * Registers event spell globally with individual object
         * @param mixed $object
         * @param string $spell
         * @param mixed $variables
         * @param \callable $callable
         * @param boolean $cacheResponse
         */
        static function register($object,$spell,$variables,callable $callable,$cacheResponse=false) {
            $id = self::id($object);
            foreach (array_filter((array)$spell, function($item) {
                return in_array($item, [
                    'read', 
                    'write', 
                    'get', 
                    'set',
                    'isset',
                    'exists',
                    'unset',
                    'remove',
                    'call',
                    'delegate',
                    'method',
                    'function',
                ]);
            }) as $spell) {
                foreach (array_unique((array) $variables) as $var) {
                    if (!isset(self::$variables[$id])) {
                        self::$variables[$id] = [];
                    }
                    
                    if(!isset(self::$variables[$id][$var])) {
                        self::$variables[$id][$var] = ($object->$var !== null) 
                                ? $object->$var 
                                : new None();
                    }

                    unset($object->$var);

                    switch($spell) {
                        case 'read':
                        case 'get':
                            $key = 'read';
                            break;
                        case 'write':
                        case 'set':
                            $key = 'write';
                            break;
                        case 'isset':
                        case 'exists':
                            $key = 'exists';
                            break;
                        case 'unset':
                        case 'remove':
                            $key = 'remove';
                            break;
                        case 'call':
                        case 'delegate':
                        case 'function':
                        case 'method':
                            $key = 'call';
                            break;
                        default:
                            $key = $spell;
                    }
                    
                    if(!isset(self::$objects[$id])) {
                        self::$objects[$id] = [$key=>[]];
                    }
                    
                    if(!isset(self::$objects[$id][$key])) {
                        self::$objects[$id][$key] = [];
                    }
                    
                    if(!isset(self::$objects[$id][$key][$var])) {
                        self::$objects[$id][$key][$var] = [];
                    }
                    
                    self::$objects[$id][$key][$var][] =  [
                        'callable' => $callable,
                        'cache' => $cacheResponse
                    ];
                }
            }
        }
        
        /**
         * Helper method to handle triggering of cached magic
         * @param string $id
         * @param string $fn
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @return mixed
         */
        private static function magicCache($id,$fn,$object,$name,$value=null) {
            if(array_key_exists($fn,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][$fn])) {
                $events = &self::$objects[$id][$fn][$name];
                $eventSize = sizeof($events);
                for($i = 0; $i < $eventSize; $i++) {
                    $data = &$events[$i];
                    
                    $cachedValue = self::trigger($object,$name,null,$data,true);
                    
                    if($cachedValue) {
                        return $cachedValue;
                    }
                }
            }
            
            if(array_key_exists($name,self::$variables[$id])) {
                return self::$variables[$id][$name];
            }
            
            return null;
        }
        
        /**
         * Helper method to handle triggering
         * @param string $id
         * @param string $fn
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @return mixed
         */
        private static function magic($id,$fn,$object,$name,$value=null) {
            if(array_key_exists($fn,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][$fn])) {
                $events = self::$objects[$id][$fn][$name];
                $eventSize = sizeof($events);
                for ($i = 0; $i < $eventSize; $i++) {
                    $data = &$events[$i];
                    
                    self::trigger($object,$name,$value,$data);
                }
            }

            if(array_key_exists($name,self::$variables[$id]) && !empty($value)) {
                return self::$variables[$id][$name] = $value;
            } 
            
            return null;
        }
        
        /**
         * Helper method that performs trigger
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @param array $eventData
         * @param boolean $cache
         * @return mixed
         */
        private static function trigger($object, $name, $value, &$eventData, $cache = false) {
            if($cache) {
                return self::triggerCache($object, $name, $value, $eventData);
            }
            
            return self::triggerDirect($object, $name, $value, $eventData);
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
                throw new Exception('Event registered on read of variable "' . $name . '" does not return a cacheable response - cannot be null');
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
        private static function triggerDirect($object, $name, $value, $eventData) {
            $callable = $eventData['callable'];
            if($callable instanceof \Closure) {
                $callable->bindTo($object,$object);
            }
            $callable($value);
        }
        
        /**
         * @param mixed $object
         * @param string $name
         * @return mixed
         * @throws Exception
         */
        static function read($object,$name) {
            $id = self::id($object);
            
            $value = self::magicCache($id, __FUNCTION__, $object, $name);
            
            if($value) {
                return $value;
            }
            
            return new None();
        }
        
        /**
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @return mixed
         */
        static function write($object,$name,$value) {
            $id = self::id($object);
            
            return self::magic($id, __FUNCTION__, $object, $name, $value);
        }
        
        /**
         * @param mixed $object
         * @param string $name
         * @return boolean
         */
        static function exists($object,$name) {
            $id = self::id($object);
            
            $value = self::magic($id, __FUNCTION__, $object, $name);
            if($value &&
               !($value instanceof None)) {
                return true;
            }
            
            return false;
        }
        
        /**
         * @param mixed $object
         * @param string $name
         */
        static function remove($object,$name) {
            $id = self::id($object);
            self::magic($id, __FUNCTION__, $object, $name);

            if(self::$variables[$id][$name]) {
                unset(self::$variables[$id][$name]);
            }
        }
        
        /**
         * @param mixed $object
         * @param string $name
         * @param array $arguments
         * @return mixed
         * @throws Exception
         */
        static function call($object,$name,$arguments=[]) {
            $id = self::id($object);
            $value = self::magicCache($id, __FUNCTION__, $object, $name);
            
            if($value) {
                return $value;
            }
        }
    }
}