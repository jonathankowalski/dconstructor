<?php

namespace JonathanKowalski\Dconstructor;


use PhpDocReader\PhpDocReader;

class Container
{

    private $docreader;
    private $container = [];
    /**
     * @var Context
     */
    private $context;
    const NULL_VALUE = '__NULL|VALUE__';

    private $ignoreCircular = true;

    const OPT_DONT_IGNORE_CIRCULAR = 1;

    public function __construct($options = 0)
    {
        $this->docreader = new PhpDocReader();
        $this->parseOptions($options);
    }

    protected function parseOptions($options)
    {
        if($options&self::OPT_DONT_IGNORE_CIRCULAR == self::OPT_DONT_IGNORE_CIRCULAR){
            $this->ignoreCircular = false;
        }
    }

    public function get($id)
    {
        $this->arrayToClassName($id);
        if(!isset($this->container[$id])){
            if(class_exists($id)){
                $this->context = new Context;
                return $this->getCheckStack($id);
            } else {
                throw new \InvalidArgumentException(sprintf("Identifier %s does not exists", $id));
            }
        }
        return $this->getFromContainer($id);
    }

    protected function arrayToClassName(&$id){
        if(is_array($id)){
            $id = implode('\\', $id);
        }
    }

    protected function getCheckStack($id)
    {
        if(!isset($this->container[$id])) {
            if ($this->context->has($id)) {
                if(!$this->ignoreCircular) {
                    throw new \Exception('circular references');
                } else {
                    return false;
                }
            }
            $this->context->add($id);
            return $this->getObjectFromClass($id);
        } else {
            return $this->getFromContainer($id);
        }
    }

    protected function getFromContainer($id){
        $isCallable = method_exists($this->container[$id],'__invoke');
        $value = $isCallable ? $this->container[$id]($this) : $this->container[$id];
        if(self::NULL_VALUE === $value){
            $value = null;
        }
        unset($this->context);
        return $value;
    }

    public function has($id, $strict = true)
    {
        if($strict){
            return isset($this->container[$id]);
        }
        return isset($this->container[$id]) || class_exists($id);
    }

    public function set($id, $value)
    {
        if(class_exists($id)){
            throw new \InvalidArgumentException("Please don't use className for id, if u wanna got an object from $id just use ->get($id)");
        }
        if(null === $value){
            $value = self::NULL_VALUE;
        }
        return $this->setInContainer($id, $value);
    }

    protected function setInContainer($id, $value)
    {
        $this->container[$id] = $value;
        return $this;
    }

    protected function getObjectFromClass($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        if($reflectionClass->isAbstract()){
            return false;
        }
        $object = $reflectionClass->newInstance();

        $properties = $reflectionClass->getProperties();
        foreach($properties as $property){
            $propertyClass = $this->docreader->getPropertyClass($property);
            if(!!$propertyClass){
                $object4Property = $this->getCheckStack($propertyClass);
                if(is_object($object4Property)) {
                    $property->setAccessible(true);
                    $property->setValue($object, $object4Property);
                }
            }
        }
        if($this->isSingleton($reflectionClass)){
            $this->setInContainer($className, $object);
        }
        $this->context->rm($className);
        return $object;
    }

    protected function isSingleton(\ReflectionClass $class)
    {
        return false !== strpos($class->getDocComment(),'@Singleton');
    }
}