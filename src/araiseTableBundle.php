<?php

declare(strict_types=1);

namespace araise\TableBundle;

use araise\TableBundle\DependencyInjection\Compiler\TableFactoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class araiseTableBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TableFactoryPass());
    }
}
