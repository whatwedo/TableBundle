<?php

declare(strict_types=1);

namespace araise\TableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('araise_table');

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('enable_turbo')->defaultFalse()->end();

        return $treeBuilder;
    }
}
