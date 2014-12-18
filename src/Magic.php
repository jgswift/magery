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
            $spells = array_filter((array)$spell, function($item) {
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
            });
            
            self::registerSpells($object, $spells, $variables, $callable, $cacheResponse);
        }
        
        /**
         * Helper method to register magic in aggregate
         * @param mixed $object
         * @param array $spells
         * @param mixed $variables
         * @param callable $callable
         * @param boolean $cacheResponse
         */
        private static function registerSpells($object,array $spells,$variables,callable $callable,$cacheResponse=false) {
            foreach($spells as $spell) {
                self::registerSpell($object, $spell, $variables, $callable,$cacheResponse);
            }
        }
        
        /**
         * Helper method to register individual spell
         * @param mixed $object
         * @param string $event
         * @param mixed $variables
         * @param callable $callable
         * @param boolean $cacheResponse
         */
        private static function registerSpell($object,$event,$variables,callable $callable,$cacheResponse=false) {
            $id = self::id($object);
            $uniqueVars = array_unique((array)$variables);
            foreach($uniqueVars as $var) {
                if(!array_key_exists($id,self::$variables)) {
                    self::$variables[$id] = [];
                }

                if(!array_key_exists($var,self::$variables[$id])) {
                    self::$variables[$id][$var] = ($object->$var !== null) 
                            ? $object->$var 
                            : new None();
                }

                unset($object->$var);

                self::commitEventAction($id, $event, $var, $callable, $cacheResponse);
            }
        }
        
        /**
         * Stores event callable and cached response in memory for quick access
         * @param string $id
         * @param string $event
         * @param string $var
         * @param callable $callable
         * @param boolean $cacheResponse
         * @return null
         */
        private static function commitEventAction($id, $event, $var, callable $callable, $cacheResponse = false) {
            $key = self::normalizeSpellName($event);

            if(is_null($key)) {
                return;
            }

            if(!array_key_exists($id,self::$objects)) {
                self::$objects[$id] = [$key=>[]];
            }

            if(!array_key_exists($key,self::$objects[$id])) {
                self::$objects[$id][$key] = [];
            }

            if(!array_key_exists($var,self::$objects[$id][$key])) {
                self::$objects[$id][$key][$var] = [];
            }

            self::$objects[$id][$key][$var][] =  [
                'callable' => $callable,
                'cache' => $cacheResponse
            ];
        }
        
        /**
         * Adds multiple naming conventions for magic
         * @param string $spell
         * @return string
         */
        private static function normalizeSpellName($spell) {
            $mapping = [
                'read' => 'read',
                'get' => 'read',
                'write' => 'write',
                'set' => 'write',
                'exists' => 'exists',
                'isset' => 'exists',
                'remove' => 'remove',
                'unset' => 'remove',
                'call' => 'call',
                'delegate' => 'call',
                'function' => 'call',
                'method' => 'call'
            ];
            
            if(array_key_exists($spell,$mapping)) {
                return $mapping[$spell];
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
            if(!(($value = self::magicEventCache($id, $fn, $object, $name, $value)) instanceof None)) {
                return $value;
            }
            
            if(!array_key_exists($id,self::$variables)) {
                self::$variables[$id] = [];
            }
            
            if(array_key_exists($name,self::$variables[$id])) {
                return self::$variables[$id][$name];
            }
            
            return null;
        }
        
        /**
         * Helper method to handle triggering of events
         * @param string $id
         * @param string $fn
         * @param mixed $object
         * @param string $name
         * @param mixed $value
         * @return mixed
         */
        private static function magicEventCache($id,$fn,$object,$name,$value) {
            if(array_key_exists($fn,self::$objects[$id]) &&
               array_key_exists($name, self::$objects[$id][$fn])) {
                $events = &self::$objects[$id][$fn][$name];
                $eventSize = sizeof($events);
                for($i = 0; $i < $eventSize; $i++) {
                    $data = &$events[$i];
                    
                    $cachedValue = Magic\Spell::trigger($object,$name,$value,$data,true);
                    
                    if($cachedValue) {
                        return $cachedValue;
                    }
                }
            }
            
            return new None();
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
                    
                    Magic\Spell::trigger($object,$name,$value,$data);
                }
            }

            if(array_key_exists($name,self::$variables[$id]) && !empty($value)) {
                return self::$variables[$id][$name] = $value;
            } 
            
            return null;
        }
                
        /**
         * @param mixed $object
         * @param string $name
         * @return mixed
         * @throws Exception
         */
        static function read($object,$name) {
            return self::call($object,$name, [], __FUNCTION__);
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
        static function call($object,$name,$arguments=[], $spell = null) {
            if(is_null($spell)) {
                $spell = __FUNCTION__;
            }
            
            $id = self::id($object);
            $value = self::magicCache($id, $spell, $object, $name, $arguments);
            
            if($value) {
                return $value;
            }
            
            return new None();
        }
    }
}