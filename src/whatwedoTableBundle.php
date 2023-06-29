<?php

declare(strict_types=1);

namespace araise\TableBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use araise\TableBundle\DependencyInjection\Compiler\TableFactoryPass;

class whatwedoTableBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TableFactoryPass());
    }
}
