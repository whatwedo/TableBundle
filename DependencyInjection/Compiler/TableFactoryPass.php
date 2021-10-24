<?php

declare(strict_types=1);
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\TableBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Factory\TableFactory;

class TableFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach (array_keys($container->findTaggedServiceIds('whatwedo.table_bundle.extension')) as $id) {
            $tableExtension = $container->getDefinition($id);

            if (! is_subclass_of($tableExtension->getClass(), ExtensionInterface::class)) {
                throw new \UnexpectedValueException(sprintf('Extensions tagged with table.extension must implement %s - %s given.', ExtensionInterface::class, $tableExtension->getClass()));
            }

            if (! call_user_func([$tableExtension->getClass(), 'isEnabled'], [$container->getParameter('kernel.bundles')])) {
                $container->removeDefinition($id);
            }
        }

        foreach (array_keys($container->findTaggedServiceIds('whatwedo.table_bundle.extension')) as $id) {
            $container->getDefinition(TableFactory::class)->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
