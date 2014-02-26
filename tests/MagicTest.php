<?php
namespace Magery\Tests {
    /**
    * Magic test case class
    * Class MagicTest
    * @package Magery
    */
    class MagicTest extends MageryTestCase {
        
        /**
         * @expectedException Magery\Exception
         */
        function testExceptionWhenCachingReadWithNullResponse() {
            $user = new Mock\User();
            $user->registerMagic('read','name',function(){},true);
            $user->name;
        }

        function testCachingResponse() {
            $c = 0;
            $attempts = 5;
            $run = function()use(&$c) {
                $c++;
                if($c>1) {
                    throw new \Exception('RunOnceOnly callable called multiple times');
                }
                return 'mockCacheResponse';
            };
            
            $user = new Mock\User();
            $user->registerMagic('read','name',$run,true);
            for($i=0;$i<$attempts;$i++) {
                $user->name;
            }
            $this->assertEquals(1,$c);
        }

        function testNonCachingResponse() {
            $c = 0;
            $attempts = 5;
            $run = function()use(&$c) {
                $c++;
                return 'mockResponse';
            };
            
            $user = new Mock\User();
            $user->registerMagic('read','name',$run,false);
            for($i=0;$i<$attempts;$i++) {
                $user->name;
            }
            $this->assertEquals($attempts,$c);
        }

        function testObjectReadSpell() {
            $user = new Mock\User();
            $user->name = new \stdClass();
            $firstName = 'Foo';
            $user->name->first = $firstName;
            
            $c = 0;
            $user->registerMagic('read','name',function()use(&$c) {
                $c++;
            });
            
            $this->assertSame($firstName,$user->name->first);
            $this->assertEquals(1,$c);
        }
        
        function testObjectWriteSpell() {
            $user = new Mock\User();
            $c = 0;
            $user->registerMagic('write', 'name', function()use(&$c) {
                $c++;
            });
            
            $user->name = new \stdClass();
            $this->assertEquals(1,$c);
            
            $user->name;
            $this->assertEquals(1,$c);
            
            $user->name = 'mockString';
            $this->assertEquals(2,$c);
        }
        
        function testArrayReadSpell() {
            $user = new Mock\User;
            $c = 0;
            
            $firstName = 'Foo';
            $user->name = ['firstName' => $firstName];
            $user->registerMagic('read','name',function()use(&$c) {
                $c++;
            });
            
            $this->assertSame($firstName,$user->name['firstName']);
            $this->assertEquals(1,$c);
            
            $user->name['firstName'];
            $this->assertEquals(2,$c);
        }
        
        function testArrayWriteSpell() {
            $user = new Mock\User();
            $c=0;
            $user->registerMagic('write','name',function()use(&$c) {
                $c++;
            });
            
            $firstName = 'Foo';
            $user->name = ['firstName' => $firstName];
            $this->assertEquals(1,$c);
        }
        
        function testObjectRemoveSpell() {
            $user = new Mock\User();
            $c = 0;
            $firstName = 'Foo';
            $user->name = $firstName;
            $user->registerMagic('remove', 'name', function()use(&$c) {
                $c++;
            });
            
            $this->assertEquals($firstName,$user->name);
            
            unset($user->name);
            $this->assertEquals($user->name,null);
            $this->assertEquals(1,$c);
        }
        
        function testObjectExistsSpell() {
            $user = new Mock\User();
            $c = 0;
            $firstName = 'Foo';
            $user->name = $firstName;
            $user->registerMagic('exists', 'name', function()use(&$c) {
                $c++;
            });
            
            $this->assertEquals($firstName,$user->name);
            
            isset($user->name);
            $this->assertSame($user->name,$firstName);
            $this->assertEquals(1,$c);
        }
        
        function testObjectCallSpell() {
            $user = new Mock\User();
            
            $c = 0;
            $user->registerMagic('call','isAllowed',function()use(&$c) {
                $c++;
                return true;
            });
            
            $result = $user->isAllowed();
            
            $this->assertEquals($result,true);
            $this->assertEquals(1,$c);
        }
    }
}
