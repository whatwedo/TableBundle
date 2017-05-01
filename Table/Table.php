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

namespace whatwedo\TableBundle\Table;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DOMNode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;
use whatwedo\TableBundle\Collection\ColumnCollection;
use whatwedo\TableBundle\Enum\FilterStateEnum;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Iterator\RowIterator;
use whatwedo\TableBundle\Model\Type\FilterTypeInterface;
use whatwedo\TableBundle\Model\Type\SimpleEnumFilterType;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Table
{
    /**
     * @var ColumnCollection
     */
    protected $columns;

    /**
     * @var int max returned rows
     */
    protected $limit = 25;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string
     */
    protected $rowRoute = '';

    /**
     * @var string
     */
    protected $exportRoute = '';

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var EntityRepository
     */
    protected $filterRepository;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        EngineInterface $templating,
        EntityRepository $filterRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $requestStack->getMasterRequest();
        $this->columns = new ColumnCollection();
        $this->templating = $templating;
        $this->filterRepository = $filterRepository;
    }

    /**
     * adds a new column
     *
     * @param string $acronym
     * @param null $type
     * @param array $options
     * @return $this
     */
    public function addColumn($acronym, $type = null, array $options)
    {
        if ($type === null) {
            $type = Column::class;
        }

        $column = new $type($acronym, $options);

        if ($column instanceof TemplateableColumnInterface) {
            $column->setTemplating($this->templating);
        }

        $this->columns->set($acronym, $column);

        return $this;
    }

    /**
     * adds a new filter
     *
     * @param $identifier
     * @param $name
     * @param FilterTypeInterface $type
     * @return Table $this
     */
    public function addFilter($identifier, $name, FilterTypeInterface $type)
    {
        $this->filters[$identifier] = new Filter($identifier, $name, $type);
        return $this;
    }

    /**
     * @param $identifier
     * @param $label
     * @return Table $this
     */
    public function overrideFilterName($identifier, $label)
    {
        $this->filters[$identifier]->setName($label);
        return $this;
    }

    /**
     * @param $identifier
     * @param $class
     * @return Table $this
     * @throws \Exception
     */
    public function simpleEnumFilter($identifier, $class)
    {
        if (!isset($this->filters[$identifier])) {
            throw new \Exception(sprintf('no Filter found for "%s". Add one first via addFilter. (simpleEnumFilter only works when field was automatically detected.)', $identifier));
        }
        $name = $this->filters[$identifier]->getName();
        $column = $this->filters[$identifier]->getType()->getColumn();
        $this->filters[$identifier] = new Filter($identifier, $name, new SimpleEnumFilterType($column, [], $class));
        return $this;
    }

    /**
     * @param $identifier
     * @return Table $this
     */
    public function removeFilter($identifier)
    {
        unset($this->filters[$identifier]);
        return $this;
    }

    /**
     * @param $label
     * @param $icon
     * @param $button
     * @param $route
     * @return Table $this
     * @throws \Exception
     */
    public function addActionItem($label, $icon, $button, $route)
    {
        /** @var ActionColumn $actionColumn */
        $actionColumn = null;
        foreach ($this->columns as $column) {
            if ($column instanceof ActionColumn) {
                $actionColumn = $column;
                break;
            }
        }
        if (is_null($actionColumn)) {
            throw new \Exception('No ActionColumn found in Table');
        }
        $actionColumn->addItem($label, $icon, $button, $route);
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return ColumnCollection
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Table
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return Table
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return string
     */
    public function getRowRoute()
    {
        return $this->rowRoute;
    }

    /**
     * @param string $rowRoute
     * @return Table
     */
    public function setRowRoute($rowRoute)
    {
        $this->rowRoute = $rowRoute;
        return $this;
    }

    /**
     * @return string
     */
    public function getExportRoute()
    {
        return $this->exportRoute;
    }

    /**
     * @param string $exportRoute
     */
    public function setExportRoute($exportRoute)
    {
        $this->exportRoute = $exportRoute;
        return $this;
    }

    /**
     *
     */
    public function loadData()
    {
        if ($this->loaded === true) {
            return;
        }

        $this->eventDispatcher->dispatch(DataLoadEvent::PRE_LOAD, new DataLoadEvent($this));

        $paginator = new Paginator($this->getQueryBuilder());
        $this->totalResults = count($paginator);
        $this->results = iterator_to_array($paginator->getIterator());

        $this->eventDispatcher->dispatch(DataLoadEvent::POST_LOAD, new DataLoadEvent($this));

        $this->loaded = true;
    }

    /**
     * @return RowIterator
     */
    public function getRows()
    {
        return new RowIterator(
            $this->getResults(),
            $this->getColumns()
        );
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        if ($this->limit === -1) {
            return 1;
        }
        return ceil($this->getTotalResults() / $this->limit);
    }


    /**
     * @return array
     */
    public function getResults()
    {
        $this->loadData();

        return $this->results;
    }

    public function setResults(array $results)
    {
        $this->loaded = true;

        $this->results = $results;
    }

    public function getOffsetResults()
    {
        if ($this->limit === -1) {
            return 0;
        }
        return ($this->getCurrentPage() - 1) * $this->limit;
    }

    /**
     * returns current page number.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        $page = $this->request->query->getInt('page', 1);

        if ($page < 1) {
            $page = 1;
        }

        return $page;
    }

    /**
     * @return string
     */
    public function renderTableOnly()
    {
        $this->loadData();
        return $this->templating->render('whatwedoTableBundle::_table.html.twig', [
            'table' => $this,
        ]);
    }

    /**
     * @return string
     */
    public function renderTable()
    {
        $this->loadData();
        return $this->templating->render('whatwedoTableBundle::table.html.twig', [
            'table' => $this,
        ]);
    }

    /**
     * @param $raw
     * @return string
     */
    public function mark($raw)
    {
        /** @var Request $request */
        $request = $this->getRequest();
        if (!($q = $request->query->get('q', false))) {
            return $raw;
        }

        $replaceRegex = '/(' . implode('|', array_map('preg_quote', explode(' ', $q), ['/'])) . ')/i';

        if (strpos($raw->__toString(), '<') === false) {
            return preg_replace($replaceRegex, '<mark class="whatwedo_search__mark">$0</mark>', $raw->__toString());
        }

        $doc = new \DOMDocument();
        $doc->loadXML('<html><body>' . $raw->__toString() . '</body></html>');
        $xp = new \DOMXPath($doc);

        $anchor = $doc->getElementsByTagName('body')->item(0);
        if (!$anchor)
        {
            throw new \Exception('Anchor element not found.');
        }

        /** @var \DOMNode $node */
        foreach ($xp->query('//text()') as $node) {
            if (!$node->parentNode) {
                continue;
            }

            $text = preg_replace($replaceRegex, '<mark class="whatwedo_search__mark">$0</mark>', $node->nodeValue);
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXML($text);
            $node->parentNode->replaceChild($fragment, $node);
        }

        $str = $doc->saveHTML($anchor->firstChild);

        if (trim($str) == '<html><body></body></html>') {
            return '';
        }

        return $str;
    }

    public function getSavedFilter($username)
    {
        $path = preg_replace('/_show$/i', '_index', $this->rowRoute);
        $qb = $this->filterRepository->createQueryBuilder('f');
        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.route', ':path'),
                    $qb->expr()->orX(
                        $qb->expr()->orX(
                            $qb->expr()->eq('f.state', FilterStateEnum::ALL),
                            $qb->expr()->eq('f.state', FilterStateEnum::SYSTEM)
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->eq('f.state', FilterStateEnum::SELF),
                            $qb->expr()->eq('f.creatorUsername', ':username')
                        )
                    )
                )
            )
            ->orderBy('f.name')
            ->setParameter('path', $path)
            ->setParameter('username', $username)
            ->getQuery()->getResult();
    }

}
