<?php
namespace Magery\Tests\Mock {
    use Magery\Mage;
    
    class User {
        use Mage {
            registerMagic as public;
        }
        
        public $name;
        protected $address;
        private $password;
        
        /**
         * 
         * @param string $password
         * @return string
         */
        function setPassword($password) {
            return $this->password = $password;
        }
        
        /**
         * 
         * @param string $address
         * @return string
         */
        function setAddress($address) {
            return $this->address = $address;
        }
        
        /**
         * 
         * @param string $name
         * @return string
         */
        function setName($name) {
            return $this->name = $name;
        }
    }
}