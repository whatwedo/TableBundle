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

namespace whatwedo\TableBundle\Factory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;

class TableFactory
{
    protected array $extensions = [];

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected RequestStack $requestStack,
        protected Environment $templating,
        protected FormatterManager $formatterManager
    ) {
    }

    public function createTable($identifier, $options = []): Table
    {
        return new Table(
            $identifier,
            $options,
            $this->eventDispatcher,
            $this->templating,
            $this->formatterManager,
            $this->extensions,
            $this->requestStack
        );
    }

    public function createDoctrineTable($identifier, $options = []): DoctrineTable
    {
        return new DoctrineTable(
            $identifier,
            $options,
            $this->eventDispatcher,
            $this->templating,
            $this->formatterManager,
            $this->extensions,
            $this->requestStack
        );
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[$extension::class] = $extension;
    }
}
