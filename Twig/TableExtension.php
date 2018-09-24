<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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

namespace whatwedo\TableBundle\Twig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\TableBundle\Factory\TableFactory;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class TableExtension extends \Twig_Extension
{
    /**
     * @var RouterInterface
     */
    protected $router;

    protected $requestStack;

    protected $tableFactory;

    public function __construct(RequestStack $requestStack, TableFactory $tableFactory, RouterInterface $router)
    {
        $this->tableFactory = $tableFactory;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('whatwedo_table', function($identifier, $options) {
                return $this->tableFactory->createTable($identifier, $options);
            }),

            new \Twig_SimpleFunction('whatwedo_doctrine_table', function($identifier, $options) {
                return $this->tableFactory->createDoctrineTable($identifier, $options);
            }),
            /**
             * generates the same route with replaced or new arguments
             */
            new \Twig_SimpleFunction('whatwedo_table_generate_route_replace_arguments', function($arguments) {
                $request = $this->requestStack->getMasterRequest();
                $attributes = array_filter($request->attributes->all(), function($key) {
                    return strpos($key, '_') !== 0;
                }, ARRAY_FILTER_USE_KEY);

                $parameters = array_replace(
                    array_merge($attributes, $request->query->all()),
                    $arguments
                );

                return $this->router->generate(
                    $request->attributes->get('_route'),
                    $parameters
                );

            })
        ];
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'whatwedo_table_table_extension';
    }
}
