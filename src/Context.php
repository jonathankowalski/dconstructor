<?php
/**
 * Created by PhpStorm.
 * User: jonathankowalski
 * Date: 14/12/2016
 * Time: 21:21
 */

namespace JonathanKowalski\Dconstructor;


class Context
{
    protected $stack;

    public function add($name){
        $this->stack .= '_'.$name.'_';
    }

    public function rm($name){
        return str_replace('_'.$name.'_','', $this->stack);
    }

    public function has($name){
        return strpos($this->stack, '_'.$name.'_');
    }
}