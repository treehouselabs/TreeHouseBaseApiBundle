<?php

namespace TreeHouse\BaseApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class TreeHouseBaseApiExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->setParameters($container, $config);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function setParameters(ContainerBuilder $container, array $config)
    {
        $this->setConfigParameters($container, $config, ['tree_house.api']);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param array            $prefixes
     */
    protected function setConfigParameters(ContainerBuilder $container, array $config, array $prefixes = [])
    {
        foreach ($config as $key => $value) {
            $newPrefixes = array_merge($prefixes, [$key]);

            if (is_array($value) && !is_numeric(key($value))) {
                $this->setConfigParameters($container, $value, $newPrefixes);

                continue;
            }

            $name = implode('.', $newPrefixes);
            $container->setParameter($name, $value);
        }
    }
}
