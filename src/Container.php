<?php
/**
 * Created by PhpStorm.
 * User: jonathankowalski
 * Date: 07/12/2016
 * Time: 12:33
 */

namespace JonathanKowalski\Dconstructor;


use PhpDocReader\PhpDocReader;

class Container
{

    private $docreader;
    private $container = [];
    private $stack = [];

    public function __construct()
    {
        $this->docreader = new PhpDocReader();
    }

    public function get($id)
    {
        if(!isset($this->container[$id])){
            if(class_exists($id)){
                $this->stack = [];
                return $this->getCheckStack($id);
            } else {
                throw new \InvalidArgumentException(sprintf("Identifier %s does not exists", $id));
            }
        }
        $isCallable = method_exists($this->container[$id],'__invoke');
        return $isCallable ? $this->container[$id]($this) : $this->container[$id];
    }

    protected function getCheckStack($id){
        if(!isset($this->container[$id])) {
            if (in_array($id, $this->stack)) {
                throw new \Exception('circular references');
            }
            $this->stack [] = $id;
            return $this->getObjectFromClass($id);
        }
        return $this->get($id);
    }

    public function has($id, $strict = true)
    {
        if($strict){
            return isset($this->container[$id]);
        }
        return isset($this->container[$id]) || class_exists($id);
    }

    public function set($id, $value){
        if(class_exists($id)){
            throw new \InvalidArgumentException("Please don't use className for id, if u wanna got an object from $id just use ->get($id)");
        }
        return $this->setInContainer($id, $value);
    }

    protected function setInContainer($id, $value){
        $this->container[$id] = $value;
        return $this;
    }

    protected function getObjectFromClass($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $object = $reflectionClass->newInstance();

        $properties = $reflectionClass->getProperties();
        foreach($properties as $property){
            $propertyClass = $this->docreader->getPropertyClass($property);
            if(!!$propertyClass){
                $object4Property = $this->getCheckStack($propertyClass);
                $property->setAccessible(true);
                $property->setValue($object, $object4Property);
            }
        }
        if($this->isSingleton($reflectionClass)){
            $this->setInContainer($className, $object);
        }
        $k = array_search($className,$this->stack);
        if(false !== $k) {
            unset($this->stack[$k]);
        }
        return $object;
    }

    protected function isSingleton(\ReflectionClass $class){
        return false !== strpos($class->getDocComment(),'@Singleton');
    }
}