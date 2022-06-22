<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class whatwedoTableExtension extends Extension implements PrependExtensionInterface
{

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('whatwedo_table.filter.save_created_by', $config['filter']['save_created_by']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (! $container->hasExtension('doctrine_migrations')) {
            return;
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, [[]]);

        $doctrineConfig = $container->getExtensionConfig('doctrine_migrations');
        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => array_merge(
                array_pop($doctrineConfig)['migrations_paths'] ?? [],
                $config['filter']['save_created_by']
                 ? [
                     'whatwedo\TableBundle\Migrations\WithCreatedBy' => '@whatwedoTableBundle/Migrations/WithCreatedBy',
                 ]
                 : [
                     'whatwedo\TableBundle\Migrations\WithoutCreatedBy' => '@whatwedoTableBundle/Migrations/WithoutCreatedBy',
                 ]
            ),
        ]);
    }
}
