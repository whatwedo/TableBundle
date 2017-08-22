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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use whatwedo\TableBundle\Collection\ColumnCollection;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Exception\DataLoaderNotAvailableException;
use whatwedo\TableBundle\Exception\ReservedColumnAcronymException;
use whatwedo\TableBundle\Iterator\RowIterator;
use whatwedo\TableBundle\Model\TableDataInterface;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Table
{
    const ACTION_COLUMN_ACRONYM = '_actions';

    const QUERY_PARAMETER_PAGE = 'page';
    const QUERY_PARAMETER_QUERY = 'query';
    const QUERY_PARAMETER_LIMIT = 'limit';

    /**
     * @var string unique table identifier
     */
    protected $identifier;

    /**
     * @var array reserved acronyms of columns
     */
    protected $reservedColumnAcronyms = [];

    /**
     * @var ColumnCollection collection of columns
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
    protected $showRoute;

    /**
     * @var string
     */
    protected $exportRoute;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * Table constructor.
     *
     * @param string                   $identifier
     * @param array                    $options
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack             $requestStack
     * @param EngineInterface          $templating
     */
    public function __construct(
        $identifier,
        $options,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        EngineInterface $templating
    ) {
        $this->identifier = $identifier;
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $requestStack->getMasterRequest();
        $this->templating = $templating;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->reservedColumnAcronyms[] = static::ACTION_COLUMN_ACRONYM;
        $this->columns = new ColumnCollection();
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'title' => null,
            'searchable' => false,
            'sortable' => false,
            'attr' => [
                'class' => null,
            ],
            'table_attr' => [
                'class' => null,
            ],
            'default_limit' => 25,
            'limit_choices' => [10, 25, 50, 100, 200],
            'table_box_template' => 'whatwedoTableBundle::table.html.twig',
            'table_template' => 'whatwedoTableBundle::tableOnly.html.twig',
        ]);

        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('attr', ['array']);
        $resolver->setAllowedTypes('searchable', ['boolean']);
        $resolver->setAllowedTypes('default_limit', ['integer']);
        $resolver->setAllowedTypes('table_box_template', ['string']);
        $resolver->setAllowedTypes('table_template', ['string']);
        $resolver->setAllowedTypes('limit_choices', ['array']);

        /*
         * Data Loader
         * ---
         * Callable Arguments:
         *  - Current Page
         *  - Limit of results (-1 equals all results)
         *
         * The callable must return a TableDataInterface.
         * you can pass an array to call_user_func
         */
        $resolver->setRequired('data_loader');
        $resolver->setAllowedTypes('data_loader', ['array', 'callable']);
    }

    /**
     * @param $key
     * @return null
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return ColumnCollection
     */
    public function getColumns()
    {
        return $this->columns;
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
        if (in_array($acronym, $this->reservedColumnAcronyms)) {
            throw new ReservedColumnAcronymException($acronym);
        }

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
     * @return ActionColumn
     */
    public function getActionColumn()
    {
        if (isset($this->columns[static::ACTION_COLUMN_ACRONYM])) {
            $this->addColumn(static::ACTION_COLUMN_ACRONYM, ActionColumn::class, []);
        }

        return $this->columns[static::ACTION_COLUMN_ACRONYM];
    }

    /**
     * @return string
     */
    public function getShowRoute()
    {
        return $this->showRoute;
    }

    /**
     * @param string $showRoute
     * @return $this
     */
    public function setShowRoute($showRoute)
    {
        $this->showRoute = $showRoute;

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
     *
     * @return $this
     */
    public function setExportRoute($exportRoute)
    {
        $this->exportRoute = $exportRoute;

        return $this;
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
     * @param $action
     * @return string
     */
    public function getActionQueryParameter($action)
    {
        return sprintf('%s_%s', $this->getIdentifier(), $action);
    }

    /**
     * returns current page number.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        $page = $this->request->query->getInt($this->getActionQueryParameter(static::QUERY_PARAMETER_PAGE), 1);

        if ($page < 1) {
            $page = 1;
        }

        return $page;
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
     * @return array
     */
    public function getResults()
    {
        return $this->results;
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
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @param int $totalResults
     */
    protected function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
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
     * @return int
     */
    public function getOffsetResults()
    {
        if ($this->limit === -1) {
            return 0;
        }
        return ($this->getCurrentPage() - 1) * $this->limit;
    }

    /**
     * loads the data
     * @throws DataLoaderNotAvailableException
     */
    public function loadData()
    {
        if (!is_callable($this->options['data_loader']) && !is_array($this->options['data_loader'])) {
            throw new DataLoaderNotAvailableException();
        }

        if ($this->loaded) {
            return;
        }

        // sets the limit of results
        $this->setLimit($this->getRequest()->query->getInt(
            $this->getActionQueryParameter(static::QUERY_PARAMETER_LIMIT),
            $this->options['default_limit']
        ));

        $this->eventDispatcher->dispatch(DataLoadEvent::PRE_LOAD, new DataLoadEvent($this));

        // loads the data from the data loader callable
        $tableData = null;

        if (is_callable($this->options['data_loader'])) {
            $tableData = ($this->options['data_loader'])($this->getCurrentPage(), $this->getLimit());
        }
        if (is_array($this->options['data_loader'])) {
            $tableData = call_user_func($this->options['data_loader'], $this->getCurrentPage(), $this->getLimit());
        }

        if (!$tableData instanceof TableDataInterface) {
            throw new \UnexpectedValueException('Table::dataLoader must return a TableDataInterface');
        }

        $this->results = $tableData->getResults();
        $this->setTotalResults($tableData->getTotalResults());

        $this->loaded = true;

        $this->eventDispatcher->dispatch(DataLoadEvent::POST_LOAD, new DataLoadEvent($this));
    }

    /**
     * @return string
     * @throws DataLoaderNotAvailableException
     */
    public function renderTable()
    {
        $this->loadData();

        return $this->templating->render($this->options['table_template'], [
            'table' => $this,
        ]);
    }

    /**
     * @return string
     * @throws DataLoaderNotAvailableException
     */
    public function renderTableBox()
    {
        $this->loadData();

        return $this->templating->render($this->options['table_box_template'], [
            'title' => $this->options['title'],
            'table' => $this,
        ]);
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return (bool) $this->options['searchable'];
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return (bool) $this->options['sortable'];
    }

    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->options['searchable']
            ? $this->request->query->get($this->getActionQueryParameter(static::QUERY_PARAMETER_QUERY))
            : '';
    }
}
