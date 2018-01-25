<?php

namespace JonathanKowalski\Dconstructor;

class Context
{
    protected $stack;

    public function add($name)
    {
        $this->stack .= '_'.$name.'_';
        return $this;
    }

    public function rm($name)
    {
        $this->stack = str_replace('_'.$name.'_', '', $this->stack);
        return $this;
    }

    public function has($name)
    {
        return false !== strpos($this->stack, '_'.$name.'_');
    }
}
