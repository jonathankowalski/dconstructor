<?php

use PHPUnit\Framework\TestCase;
use JonathanKowalski\Dconstructor\Proxy\Wrapper;
use JonathanKowalski\Dconstructor\Container;

class WrapperTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testException()
    {
        new Wrapper("/doesnotexists");
    }

    public function testGotProxy()
    {
        $wrapper = new Wrapper;
        $proxy = $wrapper->createProxy('stdClass', function(&$wrappedObject, $proxy, $method, $parameters, &$initializer){
            $wrappedObject = new stdClass();
            $initializer = null;
            return true;
        });

        $this->assertInstanceOf('stdClass', $proxy);
        $this->assertInstanceOf('\ProxyManager\Proxy\VirtualProxyInterface', $proxy);
    }

    public function testUseDir()
    {
        $dir = __DIR__ . '/../build/proxy';
        $this->assertFalse((new FilesystemIterator($dir))->valid());
        $wrapper = new Wrapper($dir);
        $wrapper->createProxy('JonathanKowalski\Dconstructor\Container', function(&$wrappedObject, $proxy, $method, $parameters, &$initializer){
            $wrappedObject = new Container();
            $initializer = null;
            return true;
        });
        $this->assertTrue((new FilesystemIterator($dir))->valid());
    }
}