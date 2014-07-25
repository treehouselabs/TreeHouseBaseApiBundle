<?php

namespace TreeHouse\BaseApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tree_house_base_api');
        $rootNode
            ->children()
                ->scalarNode('token_host')
                    ->isRequired()
                ->end()
                ->scalarNode('host')
                    ->isRequired()
                ->end()
                ->scalarNode('allowed_origins')
                    ->info('CORS: specifies which origins are allowed to access the api.')
                    ->example('http://example.org')
                    ->defaultValue('*')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
