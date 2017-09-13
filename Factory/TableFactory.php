<?php
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


use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;

class TableFactory
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var EntityRepository
     */
    protected $filterRepository;

    /**
     * @var ExtensionInterface[]
     */
    protected $extensions = [];

    /**
     * TableFactory constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack             $requestStack
     * @param EngineInterface          $templating
     * @param EntityRepository         $filterRepository
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        EngineInterface $templating,
        EntityRepository $filterRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->templating = $templating;
        $this->filterRepository = $filterRepository;
    }

    /**
     * returns a new table object
     *
     * @param string $identifier
     * @param array  $options
     *
     * @return Table
     */
    public function createTable($identifier, $options = [])
    {
        return new Table(
            $identifier,
            $options,
            $this->eventDispatcher,
            $this->requestStack,
            $this->templating,
            $this->extensions
        );
    }

    public function createDoctrineTable($identifier, $options = [])
    {
        return new DoctrineTable(
            $identifier,
            $options,
            $this->eventDispatcher,
            $this->requestStack,
            $this->templating,
            $this->extensions,
            $this->filterRepository
        );
    }

    /**
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->extensions[get_class($extension)] = $extension;
    }
}
