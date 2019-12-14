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

namespace whatwedo\TableBundle\Table;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Model\SimpleTableData;

/**
 * Class DoctrineTable
 * @package whatwedo\TableBundle\Table
 */
class DoctrineHierarchialTable extends Table
{

    /**
     * Table constructor.
     *
     * @param string $identifier
     * @param array $options
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack $requestStack
     * @param Environment $templating
     * @param FormatterManager $formatterManager
     * @param ExtensionInterface[] $extensions
     * @internal param FilterRepository $filterRepository
     */
    public function __construct(
        $identifier,
        $options,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        Environment $templating,
        FormatterManager $formatterManager,
        array $extensions
    ) {
        parent::__construct($identifier, $options, $eventDispatcher, $requestStack, $templating, $formatterManager, $extensions);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('searchable', true);
        $resolver->setDefault('sortable', true);
        $resolver->setDefault('data_loader', [$this, 'dataLoader']);
        $resolver->setRequired('query_builder');
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->options['query_builder'];
    }


    /**
     * Doctrine table data loader
     *
     * @param int $page
     * @param int $limit
     *
     * @return SimpleTableData
     */
    public function dataLoader($page, $limit)
    {
        if ($limit > 0) {
            $this->getQueryBuilder()->setMaxResults($limit);
            $this->getQueryBuilder()->setFirstResult(($page - 1) * $limit);
        }


        $result = $this->getQueryBuilder()->getQuery()->getResult();



        $collection = new \Doctrine\Common\Collections\ArrayCollection();
        $this->buildData($result, $collection, true);



        $paginator = new Paginator($this->getQueryBuilder());
        $tableData = new SimpleTableData();
        $tableData->setTotalResults(count($paginator));
        $tableData->setResults($collection->toArray());

        return $tableData;
    }


    private function buildData($list, \Doctrine\Common\Collections\ArrayCollection $collection, $rootElements = false)
    {
        foreach ($list as $item) {
            if ($rootElements) {
                if ($item->getParent()) {
                    continue;
                }
            }
            $collection->add($item);
            $this->buildData($item->getChildren(), $collection);
        }

    }


}
