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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\TableBundle\Factory\TableFactory;

class TableExtension extends AbstractExtension
{
    /**
     * @var RouterInterface
     */
    protected $router;

    protected $requestStack;

    protected $tableFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        RequestStack $requestStack,
        TableFactory $tableFactory,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->tableFactory = $tableFactory;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('whatwedo_table', function ($identifier, $options) {
                return $this->tableFactory->createTable($identifier, $options);
            }),

            new TwigFunction('whatwedo_doctrine_table', function ($identifier, $options) {
                return $this->tableFactory->createDoctrineTable($identifier, $options);
            }),
            /*
             * generates the same route with replaced or new arguments
             */
            new TwigFunction('whatwedo_table_generate_route_replace_arguments', function ($arguments) {
                if (\is_callable([$this->requestStack, 'getMainRequest'])) {
                    $request = $this->requestStack->getMainRequest();   // symfony 5.3+
                } else {
                    $request = $this->requestStack->getMasterRequest();
                }
                $attributes = array_filter($request->attributes->all(), function ($key) {
                    return 0 !== mb_strpos($key, '_');
                }, ARRAY_FILTER_USE_KEY);

                $parameters = array_replace(
                    array_merge($attributes, $request->query->all()),
                    $arguments
                );

                return $this->router->generate(
                    $request->attributes->get('_route'),
                    $parameters
                );
            }),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('whatwedo_operators', function ($data) {
                foreach (array_keys($data) as $key) {
                    $data[$key] = $this->translator->trans($data[$key]);
                }
                return json_encode($data);
            }),
        ];
    }

    public function getName()
    {
        return 'whatwedo_table_table_extension';
    }
}
