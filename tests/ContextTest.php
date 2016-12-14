<?php

use PHPUnit\Framework\TestCase;
use JonathanKowalski\Dconstructor\Context;


class ContextTest extends TestCase
{
    public function testAdd(){
        $context = new Context();
        $context->add('myname');

        $this->assertTrue($context->has('myname'));
    }

    public function testRm(){
        $context = new Context();
        $context->add('myname');
        $context->rm('myname');

        $this->assertFalse($context->has('myname'));
    }
}