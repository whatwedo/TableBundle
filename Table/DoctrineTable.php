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
use Symfony\Component\Templating\EngineInterface;
use whatwedo\TableBundle\Exception\InvalidFilterAcronymException;
use whatwedo\TableBundle\Filter\Type\FilterTypeInterface;
use whatwedo\TableBundle\Filter\Type\SimpleEnumFilterType;
use whatwedo\TableBundle\Model\SimpleTableData;
use whatwedo\TableBundle\Repository\FilterRepository;

/**
 * Class DoctrineTable
 * @package whatwedo\TableBundle\Table
 */
class DoctrineTable extends Table
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var FilterRepository
     */
    protected $filterRepository;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * Table constructor.
     *
     * @param string                   $identifier
     * @param array                    $options
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack             $requestStack
     * @param EngineInterface          $templating
     * @param FilterRepository         $filterRepository
     */
    public function __construct(
        $identifier,
        $options,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        EngineInterface $templating,
        FilterRepository $filterRepository
    ) {
        $this->filterRepository = $filterRepository;

        parent::__construct($identifier, $options, $eventDispatcher, $requestStack, $templating);
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
     * adds a new filter
     *
     * @param $acronym
     * @param $name
     * @param FilterTypeInterface $type
     * @return DoctrineTable $this
     */
    public function addFilter($acronym, $name, FilterTypeInterface $type)
    {
        $this->filters[$acronym] = new Filter($acronym, $name, $type);

        return $this;
    }

    /**
     * @param $acronym
     * @return DoctrineTable $this
     */
    public function removeFilter($acronym)
    {
        unset($this->filters[$acronym]);

        return $this;
    }

    /**
     * shortcut to override the name of a filter
     *
     * @param $acronym
     * @param $label
     * @return DoctrineTable $this
     */
    public function overrideFilterName($acronym, $label)
    {
        $this->getFilter($acronym)->setName($label);

        return $this;
    }

    /**
     * creates a simple enum filter
     *
     * @param $acronym
     * @param $class
     * @return DoctrineTable $this
     * @throws \Exception
     */
    public function configureSimpleEnumFilter($acronym, $class)
    {
        $filter = $this->getFilter($acronym);
        $name = $filter->getName();
        $column = $filter->getType()->getColumn();
        $this->filters[$acronym] = new Filter($acronym, $name, new SimpleEnumFilterType($column, [], $class));

        return $this;
    }

    /**
     * @return array|Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $acronym
     * @return Filter
     */
    public function getFilter($acronym)
    {
        if (!isset($this->filters[$acronym])) {
            throw new InvalidFilterAcronymException($acronym);
        }

        return $this->filters[$acronym];
    }

    /**
     * returns all saved filters of a user
     *
     * @param $username
     * @return \whatwedo\TableBundle\Entity\Filter[]
     */
    public function getSavedFilter($username)
    {
        // TODO so den path zu definieren ist bekloppt - muss optimiert werden.
        $path = preg_replace('/_show$/i', '_index', $this->showRoute);

        return $this->filterRepository->findSavedFilter($path, $username);
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
        $this->getQueryBuilder()->setMaxResults($limit);
        $this->getQueryBuilder()->setFirstResult(($page - 1) * $limit);

        $paginator = new Paginator($this->getQueryBuilder());
        $tableData = new SimpleTableData();
        $tableData->setTotalResults(count($paginator));
        $tableData->setResults(iterator_to_array($paginator->getIterator()));

        return $tableData;
    }
}
