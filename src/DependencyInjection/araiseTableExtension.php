<?php

declare(strict_types=1);

namespace araise\TableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class araiseTableExtension extends Extension
{
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration($container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('araise_table.enable_turbo', $config['enable_turbo']);
        $container->setParameter('araise.enable_turbo', $config['enable_turbo']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
