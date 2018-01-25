<?php

namespace JonathanKowalski\Dconstructor\Proxy;

use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

class Wrapper
{

    private $proxiesDirectory;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyManager;

    public function __construct($proxiesDirectory = false)
    {
        if (false !== $proxiesDirectory) {
            if (!is_writable($proxiesDirectory)) {
                throw new \InvalidArgumentException("$proxiesDirectory must be a writable location");
            }
            $this->proxiesDirectory = $proxiesDirectory;
        }
    }

    /**
     * @param string $className
     * @param \Closure $initializer
     * @return \ProxyManager\Proxy\VirtualProxyInterface
     */
    public function createProxy($className, \Closure $initializer)
    {
        if (!$this->proxyManager) {
            $this->proxyManager = $this->createProxyManager();
        }

        return $this->proxyManager->createProxy($className, $initializer);
    }

    private function createProxyManager()
    {
        $config = new Configuration;

        if (!!$this->proxiesDirectory) {
            $config->setProxiesTargetDir($this->proxiesDirectory);
            $config->setGeneratorStrategy(new FileWriterGeneratorStrategy(new FileLocator($this->proxiesDirectory)));
            spl_autoload_register($config->getProxyAutoloader());
        } else {
            $config->setGeneratorStrategy(new EvaluatingGeneratorStrategy);
        }

        return new LazyLoadingValueHolderFactory($config);
    }
}
