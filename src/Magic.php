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
         * @param mixed $object
         * @param string $name
         * @return mixed
         * @throws Exception
         */
        static function read($object,$name) {
            $id = self::id($object);
            if(array_key_exists(__FUNCTION__,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][__FUNCTION__])) {
                $eventSize = sizeof(self::$objects[$id][__FUNCTION__][$name]);
                for($i = 0; $i < $eventSize; $i++) {
                    if(self::$objects[$id][__FUNCTION__][$name][$i]['cache'] && 
                        isset(self::$objects[$id][__FUNCTION__][$name][$i]['response'])) {
                        return self::$objects[$id][__FUNCTION__][$name][$i]['response'];
                    }

                    $callable = self::$objects[$id][__FUNCTION__][$name][$i]['callable'];
                    if($callable instanceof \Closure) {
                        $callable->bindTo($object,$object);
                    }
                    
                    $response = call_user_func($callable);
                    
                    if(self::$objects[$id][__FUNCTION__][$name][$i]['cache'] && 
                        is_null($response)) {
                        throw new Exception('Event registered on read of variable "' . $name . '" does not return a cacheable response - cannot be null');
                    }

                    if(isset($response)) {
                        if (self::$objects[$id][__FUNCTION__][$name][$i]['cache']) {
                            self::$objects[$id][__FUNCTION__][$name][$i]['response'] = $response;
                            return self::$objects[$id][__FUNCTION__][$name][$i]['response'];
                        }
                        
                        return $response;
                    }
                }
            }
            
            if(array_key_exists($name,self::$variables[$id])) {
                return self::$variables[$id][$name];
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
            if(array_key_exists($name, self::$objects[$id][__FUNCTION__])) {
                $eventSize = sizeof(self::$objects[$id][__FUNCTION__][$name]);
                for ($i = 0; $i < $eventSize; $i++) {
                    $callable = self::$objects[$id][__FUNCTION__][$name][$i]['callable'];
                    if($callable instanceof \Closure) {
                        $callable->bindTo($object,$object);
                    }
                    $callable($value);
                }
            }

            if(self::$variables[$id][$name]) {
                self::$variables[$id][$name] = $value;
            }

            return self::$variables[$id][$name];
        }
        
        /**
         * @param mixed $object
         * @param string $name
         * @return boolean
         */
        static function exists($object,$name) {
            $id = self::id($object);
            if (array_key_exists(__FUNCTION__,self::$objects[$id]) &&
                array_key_exists($name, self::$objects[$id][__FUNCTION__])) {
                $eventSize = sizeof(self::$objects[$id][__FUNCTION__][$name]);
                for ($i = 0; $i < $eventSize; $i++) {
                    $callable = self::$objects[$id][__FUNCTION__][$name][$i]['callable'];
                    if($callable instanceof \Closure) {
                        $callable->bindTo($object,$object);
                    }
                    $callable();
                }
            }

            if(array_key_exists($name,self::$variables[$id]) &&
               !(self::$variables[$id][$name] instanceof None)) {
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
            if(array_key_exists(__FUNCTION__,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][__FUNCTION__])) {
                $eventSize = sizeof(self::$objects[$id][__FUNCTION__][$name]);
                for ($i = 0; $i < $eventSize; $i++) {
                    $callable = self::$objects[$id][__FUNCTION__][$name][$i]['callable'];
                    if($callable instanceof \Closure) {
                        $callable->bindTo($object,$object);
                    }
                    $callable();
                }
            }

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
            if(array_key_exists(__FUNCTION__,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][__FUNCTION__])) {
                $eventSize = sizeof(self::$objects[$id][__FUNCTION__][$name]);
                for($i = 0; $i < $eventSize; $i++) {
                    if(self::$objects[$id][__FUNCTION__][$name][$i]['cache'] && 
                        isset(self::$objects[$id][__FUNCTION__][$name][$i]['response'])) {
                        return self::$objects[$id][__FUNCTION__][$name][$i]['response'];
                    }

                    $callable = self::$objects[$id][__FUNCTION__][$name][$i]['callable'];
                    if($callable instanceof \Closure) {
                        $callable->bindTo($object,$object);
                    }
                    
                    $response = call_user_func_array($callable,$arguments);
                    if(self::$objects[$id][__FUNCTION__][$name][$i]['cache'] && 
                        is_null($response)) {
                        throw new Exception('Event registered on read of variable "' . $name . '" does not return a cacheable response - cannot be null');
                    }

                    if(isset($response)) {
                        if (self::$objects[$id][__FUNCTION__][$name][$i]['cache']) {
                            self::$objects[$id][__FUNCTION__][$name][$i]['response'] = $response;
                            return self::$objects[$id][__FUNCTION__][$name][$i]['response'];
                        }
                        
                        return $response;
                    }
                }
            }
        }
    }
}
