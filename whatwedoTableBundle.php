<?php

namespace whatwedo\TableBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use whatwedo\TableBundle\DependencyInjection\Compiler\TableFactoryPass;

/**
 * Class whatwedoTableBundle.
 */
class whatwedoTableBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TableFactoryPass());
    }
}
