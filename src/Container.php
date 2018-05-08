<?php

namespace JonathanKowalski\Dconstructor;

use JonathanKowalski\Dconstructor\Proxy\Wrapper;
use JonathanKowalski\Dconstructor\Exception\NotFoundException;
use PhpDocReader\AnnotationException;
use PhpDocReader\PhpDocReader;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
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
    private $proxyDir = false;

    const OPT_DONT_IGNORE_CIRCULAR = 1;
    const OPT_USE_TMPDIR_4_PROXIES = 2;

    public function __construct($options = 0)
    {
        $this->parseOptions($options);
        $this->docreader = new PhpDocReader;
        $this->proxyManager = new Wrapper($this->proxyDir);
    }

    private function parseOptions($options)
    {
        if ($options&self::OPT_DONT_IGNORE_CIRCULAR == self::OPT_DONT_IGNORE_CIRCULAR) {
            $this->ignoreCircular = false;
        }
        if ($options&self::OPT_USE_TMPDIR_4_PROXIES == self::OPT_USE_TMPDIR_4_PROXIES) {
            $this->proxyDir = sys_get_temp_dir();
        }
    }

    /**
     * @param string $id
     * @return bool|mixed|null|\ProxyManager\Proxy\VirtualProxyInterface
     * @throws \Exception
     */
    public function get($id)
    {
        $id = $this->arrayToClassName($id);
        if (!isset($this->container[$id])) {
            if (class_exists($id)) {
                return $this->getCheckContext($id, new Context);
            } else {
                throw new NotFoundException($id);
            }
        }
        return $this->getFromContainer($id);
    }

    private function arrayToClassName($id)
    {
        if (is_array($id)) {
            $id = implode('\\', $id);
        }
        return $id;
    }

    /**
     * @param $id
     * @param Context $context
     * @return bool|mixed|null|\ProxyManager\Proxy\VirtualProxyInterface
     * @throws \Exception
     */
    private function getCheckContext($id, Context $context)
    {
        if (!$id) {
            return false;
        }
        if (!isset($this->container[$id])) {
            if ($context->has($id)) {
                if (!$this->ignoreCircular) {
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

    private function getFromContainer($id)
    {
        $isCallable = method_exists($this->container[$id], '__invoke');
        $value = $isCallable ? $this->container[$id]($this) : $this->container[$id];
        if (self::NULL_VALUE === $value) {
            $value = null;
        }
        return $value;
    }

    public function has($id, $strict = true)
    {
        return isset($this->container[$id]) || (!$strict && class_exists($id));
    }

    /**
     * @param $id
     * @param $value
     * @return Container
     */
    public function set($id, $value)
    {
        if (class_exists($id)) {
            throw new \InvalidArgumentException(
                "Please don't use className for id, if u wanna got an object from $id just use ->get($id)"
            );
        }
        if (null === $value) {
            $value = self::NULL_VALUE;
        }
        return $this->setInContainer($id, $value);
    }

    private function setInContainer($id, $value)
    {
        $this->container[$id] = $value;
        return $this;
    }

    /**
     * @param $className
     * @param Context $context
     * @return bool|\ProxyManager\Proxy\VirtualProxyInterface
     * @throws \ReflectionException
     */
    private function getObjectFromClass($className, Context $context)
    {
        $reflectionClass = new \ReflectionClass($className);
        if ($reflectionClass->isAbstract()) {
            return false;
        }
        $object = $this->getProxyObject($reflectionClass, $context);
        if ($this->isSingleton($reflectionClass)) {
            $this->setInContainer($className, $object);
        }
        return $object;
    }

    private function getProxyObject(\ReflectionClass $class, Context $context)
    {
        return $this->proxyManager->createProxy(
            $class->name,
            function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($class, $context) {
                $wrappedObject = $class->newInstance();
                $initializer = null;
                return $this->populateProperties($class->getProperties(), $context, $wrappedObject);
            }
        );
    }

    /**
     * @param \ReflectionProperty[] $properties
     * @param Context $context
     * @param $object
     * @return bool
     * @throws \Exception
     */
    private function populateProperties($properties, Context $context, $object)
    {
        $property = array_pop($properties);
        if (!!$property) {
            $object4Property = $this->getObject4Property($property, $context);
            if (!!$object4Property) {
                $this->setValueProperty($property, $object, $object4Property);
            }
            return $this->populateProperties($properties, $context, $object);
        }
        return true;
    }

    /**
     * @param \ReflectionProperty $property
     * @param Context $context
     * @return null|\ProxyManager\Proxy\VirtualProxyInterface
     * @throws \Exception
     */
    private function getObject4Property(\ReflectionProperty $property, Context $context)
    {
        try {
            $propertyClass = $this->docreader->getPropertyClass($property);
        } catch (AnnotationException $e) {
            return null;
        }
        $object4Property = $this->getCheckContext($propertyClass, $context);
        if (is_object($object4Property)) {
            return $object4Property;
        }
        return null;
    }

    private function setValueProperty(\ReflectionProperty $property, $object, $value)
    {
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function isSingleton(\ReflectionClass $class)
    {
        return false !== strpos($class->getDocComment(), '@Singleton');
    }
}
