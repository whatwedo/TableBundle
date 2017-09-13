<?php

namespace whatwedo\TableBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use whatwedo\TableBundle\DependencyInjection\Compiler\TableFactoryPass;

/**
 * Class whatwedoTableBundle
 * @package whatwedo\TableBundle
 */
class whatwedoTableBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TableFactoryPass());
    }

}
