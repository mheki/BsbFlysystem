<?php

namespace BsbFlysystem\Adapter\Factory;

use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;

abstract class AbstractAdapterFactory implements MutableCreationOptionsInterface
{
    /**
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->setCreationOptions($options);
    }

    /**
     * Set creation options
     *
     * @param  array $options
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Merges the options given from the ServiceLocator Config object with the create options of the class.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $requestedName
     */
    protected function mergeMvcConfig(ServiceLocatorInterface $serviceLocator, $requestedName)
    {
        while (is_callable([$serviceLocator, 'getServiceLocator'])) {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }

        $config = $serviceLocator->has('Config') ? $serviceLocator->get('Config') : [];

        if (!isset($config['bsb_flysystem']['adapters'][$requestedName]['options']) ||
            !is_array(($config['bsb_flysystem']['adapters'][$requestedName]['options']))
        ) {
            return;
        }

        $this->options = ArrayUtils::merge(
            $config['bsb_flysystem']['adapters'][$requestedName]['options'],
            $this->options
        );
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return LazyLoadingValueHolderFactory
     * @throws \InvalidArgumentException
     */
    public function getLazyFactory(ServiceLocatorInterface $serviceLocator)
    {
        while (is_callable([$serviceLocator, 'getServiceLocator'])) {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }

        $config = $serviceLocator->has('Config') ? $serviceLocator->get('Config') : [];

        $config['lazy_services'] = ArrayUtils::merge(
            isset($config['lazy_services']) ? $config['lazy_services'] : [],
            $config['bsb_flysystem']['adapter_manager']['lazy_services']
        );

        if (!isset($config['lazy_services'])) {
            throw new \InvalidArgumentException('Missing "lazy_services" config key');
        }

        $lazyServices = $config['lazy_services'];

        $factoryConfig = new Configuration();

        if (isset($lazyServices['proxies_namespace'])) {
            $factoryConfig->setProxiesNamespace($lazyServices['proxies_namespace']);
        }

        if (isset($lazyServices['proxies_target_dir'])) {
            $factoryConfig->setProxiesTargetDir($lazyServices['proxies_target_dir']);
        }

        if (!isset($lazyServices['write_proxy_files']) || !$lazyServices['write_proxy_files']) {
            $factoryConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        }

        spl_autoload_register($factoryConfig->getProxyAutoloader());

        return new LazyLoadingValueHolderFactory($factoryConfig);
    }

    /**
     * Implement in adapter
     *
     * @throw \UnexpectedValueException
     * @return null
     */
    abstract protected function validateConfig();
}
