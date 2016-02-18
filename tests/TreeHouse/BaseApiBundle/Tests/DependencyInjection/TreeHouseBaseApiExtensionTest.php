<?php

namespace TreeHouse\BaseApiBundle\Tests\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use TreeHouse\BaseApiBundle\DependencyInjection\TreeHouseBaseApiExtension;

class TreeHouseBaseApiExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConfiguration()
    {
        $container = $this->getContainer('complete.yml');

        // test parameters
        $parameters = [
            'tree_house.api.token_host'      => 'tokens.example.org',
            'tree_house.api.host'            => 'api.example.org',
            'tree_house.api.allowed_origins' => 'acme.org',
        ];

        foreach ($parameters as $name => $value) {
            $this->assertTrue($container->hasParameter($name));
            $this->assertEquals($value, $container->getParameter($name));
        }

        $this->assertTrue($container->hasDefinition('tree_house.api.security.user_encoder'));
        $this->assertTrue($container->hasDefinition('tree_house.api.security.security_context'));
    }

    private function getContainer($file, $parameters = [], $debug = false)
    {
        $container = new ContainerBuilder(new ParameterBag(array_merge($parameters, ['kernel.debug' => $debug])));
        $container->registerExtension(new TreeHouseBaseApiExtension());

        $locator = new FileLocator(__DIR__ . '/../Fixtures');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
