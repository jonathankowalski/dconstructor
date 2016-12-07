<?php

use PHPUnit\Framework\TestCase;
use JonathanKowalski\Dconstructor\Container;

class ContainerTest extends TestCase
{
    public function testHas(){
        $container = new Container();

        $container->set('foo','bar');

        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('bar'));
    }

    public function testSetNullValue(){
        $container = new Container();

        $container->set('foo', null);
        $this->assertNull($container->get('foo'));
    }

    public function testKeepObject(){
        $container = new Container();

        $o = new stdClass();
        $container->set('foo', $o);

        $this->assertSame($o, $container->get('foo'));
    }

    public function testCreateObject(){
        $container = new Container();

        $o = $container->get('stdClass');
        $this->assertInstanceOf('stdClass', $o);
    }

    public function testSetGetSet(){
        $container = new Container();

        $container->set('foo','bar');
        $container->get('foo');
        $container->set('foo','hello');

        $this->assertSame('hello', $container->get('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidId(){
        $container = new Container();

        $container->set('stdClass', 'myval');
    }
}