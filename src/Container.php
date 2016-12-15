<?php

namespace JonathanKowalski\Dconstructor;


use JonathanKowalski\Dconstructor\Proxy\Wrapper;
use PhpDocReader\PhpDocReader;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

class Container
{

    /**
     * @var PhpDocReader
     */
    private $docreader;
    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyManager;
    private $container = [];
    const NULL_VALUE = '__NULL|VALUE__';

    private $ignoreCircular = true;

    const OPT_DONT_IGNORE_CIRCULAR = 1;
    const OPT_USE_TMPDIR_4_PROXIES = 2;

    public function __construct($options = 0)
    {
        $this->docreader = new PhpDocReader;
        $proxyDir = ($options&self::OPT_USE_TMPDIR_4_PROXIES == self::OPT_USE_TMPDIR_4_PROXIES) ? sys_get_temp_dir() : false;
        $this->proxyManager = new Wrapper($proxyDir);
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
                return $this->getCheckContext($id, new Context);
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

    protected function getCheckContext($id, Context $context)
    {
        if(!isset($this->container[$id])) {
            if ($context->has($id)) {
                if(!$this->ignoreCircular) {
                    throw new \Exception('circular references');
                } else {
                    return false;
                }
            }
            $context->add($id);
            return $this->getObjectFromClass($id, $context);
        } else {
            return $this->getFromContainer($id);
        }
    }

    protected function getFromContainer($id)
    {
        $isCallable = method_exists($this->container[$id],'__invoke');
        $value = $isCallable ? $this->container[$id]($this) : $this->container[$id];
        if(self::NULL_VALUE === $value){
            $value = null;
        }
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

    protected function getObjectFromClass($className, Context $context)
    {
        $reflectionClass = new \ReflectionClass($className);
        if($reflectionClass->isAbstract()){
            return false;
        }
        $object = $this->getProxyObject($reflectionClass, $context);
        if($this->isSingleton($reflectionClass)){
            $this->setInContainer($className, $object);
        }
        return $object;
    }

    protected function getProxyObject(\ReflectionClass $class, Context $context)
    {
        return $this->proxyManager->createProxy($class->name,
            function(&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($class, $context){
                $wrappedObject = $class->newInstance();
                $properties = $class->getProperties();
                foreach($properties as $property){
                    $propertyClass = $this->docreader->getPropertyClass($property);
                    if(!!$propertyClass){
                        $object4Property = $this->getCheckContext($propertyClass, $context);
                        if(is_object($object4Property)) {
                            $property->setAccessible(true);
                            $property->setValue($wrappedObject, $object4Property);
                        }
                    }
                }
                $initializer = null;
                return true;
            });
    }

    protected function isSingleton(\ReflectionClass $class)
    {
        return false !== strpos($class->getDocComment(),'@Singleton');
    }
}