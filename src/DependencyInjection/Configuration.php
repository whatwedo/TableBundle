<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('whatwedo_table');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('save_created_by')->defaultFalse()->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
