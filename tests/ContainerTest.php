<?php

require_once "Mocks.php";

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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIdDontExists(){
        $container = new Container();
        $container->get('foo');
    }

    public function testSingleton() {

        $container = new Container();
        $notsA = $container->get('NotSingleton');
        $this->assertInstanceOf('NotSingleton', $notsA);
        $notsB = $container->get('NotSingleton');
        $this->assertNotSame($notsA, $notsB);

        $a = $container->get('Singleton');
        $this->assertInstanceOf('Singleton', $a);
        $b = $container->get('Singleton');
        $this->assertSame($a, $b);

    }

    public function testHasUnstrict(){
        $container = new Container();

        $this->assertFalse($container->has('Singleton'));

        $this->assertTrue($container->has('Singleton', false));
    }

    public function testCallable(){
        $container = new Container();

        $container->set('foo', function(){
           return new stdClass();
        });

        $this->assertInstanceOf('stdClass', $container->get('foo'));
    }

    public function testDepends(){
        $container = new Container();

        $depends = $container->get('Depends');

        $this->assertInstanceOf('Depends', $depends);
        $this->assertInstanceOf('Singleton', $depends->getSingleton());

        $this->assertSame($container->get('Singleton'), $depends->getSingleton());
    }

    public function testNs(){

        $container = new Container();

        $mock = $container->get('Mocks\Mock');
        $this->assertInstanceOf('Mocks\Mock', $mock);

        $this->assertInstanceOf('Singleton', $mock->getSingleton());
        $this->assertInstanceOf('Mocks\Singleton', $mock->getNsSingleton());

        $this->assertSame($container->get('Singleton'), $mock->getSingleton());
    }

    public function testArrayNotation(){
        $container = new Container();
        $mock = $container->get(['Mocks','Mock']);
        $this->assertInstanceOf('Mocks\Mock', $mock);

        $singleArray = $container->get(['Mocks','Singleton']);
        $single = $container->get('Mocks\Singleton');

        $this->assertSame($single, $singleArray);
    }

    public function testKeepValue(){
        $container = new Container();
        $container->set('foo',5);

        $this->assertEquals(5, $container->get('foo'));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage circular references
     */
    public function testCircular(){
        $container = new Container(Container::OPT_DONT_IGNORE_CIRCULAR);
        $container->get('CircularA');
    }

    /**
     * Abstract classes must be ignored
     */
    public function testAbstract(){
        $container = new Container();
        $o = $container->get('UseAbstract');

        $this->assertInstanceOf('UseAbstract', $o);

        $parser = $container->get('Parser');
        $this->assertFalse($parser);
    }
}

/**
 * Class Singleton
 * @Singleton
 */
class Singleton {

}

class NotSingleton {

}

class Depends {

    /**
     * @var Singleton
     */
    private $singleton;

    public function getSingleton(){
        return $this->singleton;
    }
}

class CircularA {

    /**
     * @var CircularB
     */
    private $circular;
}

class CircularB {

    /**
     * @var CircularA
     */
    private $circular;
}

abstract class Parser {}

class UseAbstract {
    /**
     * @var Parser
     */
    private $parser;
}