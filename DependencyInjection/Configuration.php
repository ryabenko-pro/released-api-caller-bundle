<?php

namespace Released\ApiCallerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('released_api_caller');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode("cases")->requiresAtLeastOneElement()->prototype('array')
            ->children()
                ->scalarNode("domain")->isRequired()->end()
                ->arrayNode("endpoints")->requiresAtLeastOneElement()->prototype('array')
                ->children()
                    ->scalarNode("name")->isRequired()->end()
                    ->scalarNode("method")->defaultValue("GET")->end()
                    ->scalarNode("path")->isRequired()->end()
                    ->scalarNode("request_class")->end()
                    ->scalarNode("response_class")->end()
                    ->scalarNode("response_format")->end()
                    ->variableNode('params')->end()
                    ->variableNode('headers')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
