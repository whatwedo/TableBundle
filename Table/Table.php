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
use Twig\Environment;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Collection\ColumnCollection;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Exception\DataLoaderNotAvailableException;
use whatwedo\TableBundle\Exception\ReservedColumnAcronymException;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\PaginationExtension;
use whatwedo\TableBundle\Extension\SearchExtension;
use whatwedo\TableBundle\Iterator\RowIterator;
use whatwedo\TableBundle\Model\TableDataInterface;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Table
{
    const ACTION_COLUMN_ACRONYM = '_actions';

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
     * @var FormatterManager $formatterManager
     */
    protected $formatterManager;

    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var Environment
     */
    protected $templating;

    /**
     * @var string|callable
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
     * @var ExtensionInterface[]
     */
    protected $extensions;

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
     */
    private $sortedColumns;

    public function __construct(
        $identifier,
        $options,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        Environment $templating,
        FormatterManager $formatterManager,
        array $extensions
    ) {
        $this->identifier = $identifier;
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $requestStack->getMasterRequest();
        $this->templating = $templating;
        $this->extensions = $extensions;
        $this->formatterManager = $formatterManager;

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
            'sortable' => true,
            'attr' => [
                'class' => null,
            ],
            'table_attr' => [
                'class' => null,
            ],
            'default_limit' => 25,
            'limit_choices' => [10, 25, 50, 100, 200],
            'table_box_template' => '@whatwedoTable/table.html.twig',
            'table_template' => '@whatwedoTable/tableOnly.html.twig',
            'default_sort' => [],
        ]);

        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('attr', ['array']);
        $resolver->setAllowedTypes('searchable', ['boolean']);
        $resolver->setAllowedTypes('default_limit', ['integer']);
        $resolver->setAllowedTypes('table_box_template', ['string']);
        $resolver->setAllowedTypes('table_template', ['string']);
        $resolver->setAllowedTypes('limit_choices', ['array']);
        $resolver->setAllowedTypes('default_sort', ['array']);

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
     * @return ColumnCollection|Column[]
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
    public function addColumn($acronym, $type = null, array $options = [])
    {
        if (in_array($acronym, $this->reservedColumnAcronyms)) {
            throw new ReservedColumnAcronymException($acronym);
        }

        if ($type === null) {
            $type = Column::class;
        }

        $column = new $type($acronym, $options);

        // only DoctrineTable can sort nested properties. Therefore disable them for other tables.
        if (!$this instanceof DoctrineTable && $column instanceof SortableColumnInterface && $column->isSortable()) {
            if (strpos($column->getSortExpression(), '.') !== false) {
                $column->setSortable(false);
            }
        }

        if ($column instanceof TemplateableColumnInterface) {
            $column->setTemplating($this->templating);
        }

        if ($column instanceof SortableColumnInterface) {
            $column->setTableIdentifier($this->getIdentifier());
        }

        if ($column instanceof FormattableColumnInterface) {
            $column->setFormatterManager($this->formatterManager);
        }

        $this->columns->set($acronym, $column);

        return $this;
    }

    /**
     * @param string $acronym
     * @return $this
     */
    public function removeColumn($acronym)
    {
        $this->columns->remove($acronym);
        return $this;
    }

    /**
     * @param string $acronym
     * @param array $newOptions
     * @return $this
     */
    public function overrideColumnOptions($acronym, array $newOptions)
    {
        /** @var AbstractColumn $column */
        $column = $this->columns->get($acronym);
        $column->overrideOptions($newOptions);
        return $this;
    }

    /**
     * @return ActionColumn
     */
    public function getActionColumn()
    {
        if (!isset($this->columns[static::ACTION_COLUMN_ACRONYM])) {
            $column = new ActionColumn(static::ACTION_COLUMN_ACRONYM, []);
            $column->setTemplating($this->templating);
            $this->columns->set(static::ACTION_COLUMN_ACRONYM, $column);
        }

        return $this->columns[static::ACTION_COLUMN_ACRONYM];
    }

    /**
     * @return string
     */
    public function getShowRoute($row)
    {
        if(is_callable($this->showRoute)) return call_user_func($this->showRoute, $row);

        return $this->showRoute;
    }

    /**
     * @param string|callable $showRoute
     * @return $this
     */
    public function setShowRoute($showRoute)
    {
        $this->showRoute = $showRoute;

        return $this;
    }

    /**
     * @return Request
     */
    public function getExportRoute()
    {
        return $this->exportRoute;
    }

    /**
     * @param string $exportRoute
     * @return Table
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
//        if($this->getIdentifier() === 'index') return $action;

        return sprintf('%s_%s', str_replace('.', '_', $this->getIdentifier()), $action);
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

        $currentPage = 1;
        $limit = -1;
        if ($this->hasExtension(PaginationExtension::class)) {
            /** @var PaginationExtension $paginationExtension */
            $paginationExtension = $this->getExtension(PaginationExtension::class);
            $paginationExtension->setLimit($this->options['default_limit']);
            $currentPage = $paginationExtension->getCurrentPage();
            $limit = $paginationExtension->getLimit();
        }

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::PRE_LOAD);

        // loads the data from the data loader callable
        $tableData = null;

        if (is_callable($this->options['data_loader'])) {
            $tableData = ($this->options['data_loader'])($currentPage, $limit);
        }
        if (is_array($this->options['data_loader'])) {
            $tableData = call_user_func($this->options['data_loader'], $currentPage, $limit);
        }

        if (!$tableData instanceof TableDataInterface) {
            throw new \UnexpectedValueException('Table::dataLoader must return a TableDataInterface');
        }

        $this->results = $tableData->getResults();

        if ($this->hasExtension(PaginationExtension::class)) {
            /** @var PaginationExtension $paginationExtension */
            $paginationExtension->setTotalResults($tableData->getTotalResults());
        }

        $this->loaded = true;

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::POST_LOAD);
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
     * @param string $extension
     * @return ExtensionInterface
     */
    public function getExtension($extension)
    {
        if (!$this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf(
                'Extension %s is not enabled. Please configure it first.',
                $extension
            ));
        }
        return $this->extensions[$extension]->setTableIdentifier($this->identifier);
    }

    /**
     * @param string $extension
     */
    public function removeExtension($extension)
    {
        if ($this->hasExtension($extension)) {
            unset($this->extensions[$extension]);
        }
    }

    /**
     * @return ExtensionInterface|SearchExtension
     */
    public function getSearchExtension()
    {
        return $this->hasExtension(SearchExtension::class)
            ? $this->getExtension(SearchExtension::class)
            : null
        ;
    }

    /**
     * @return ExtensionInterface|PaginationExtension
     */
    public function getPaginationExtension()
    {
        return $this->hasExtension(PaginationExtension::class)
            ? $this->getExtension(PaginationExtension::class)
            : null
        ;
    }

    /**
     * @return ExtensionInterface|FilterExtension
     */
    public function getFilterExtension()
    {
        return $this->hasExtension(FilterExtension::class)
            ? $this->getExtension(FilterExtension::class)
            : null
        ;
    }

    /**
     * @param string $extension
     * @return boolean
     */
    public function hasExtension($extension)
    {
        return array_key_exists($extension, $this->extensions);
    }

    public function getDefaultSortColumns() {
        return $this->options['default_sort'];
    }

    private function getSortOrderFromQuery(SortableColumnInterface $column) {
        $query = $this->request->query;
        if($query->get($column->getOrderEnabledQueryParameter()) !== '1') return null;

        $order = $query->get($column->getOrderAscQueryParameter());
        if($order === null) return null;

        return $order ? 'ASC' : 'DESC';
    }

    public function getSortOrder(AbstractColumn $column) {
        if(!$this->isSortable()) return null;

        if(!$column instanceof SortableColumnInterface || !$column->isSortable()) return null;

        $sortedColumns = $this->getSortedColumns();
        $sortExpression = $column->getSortExpression();

        if(array_key_exists($sortExpression, $sortedColumns)) {
            return $sortedColumns[$sortExpression];
        }

        return null;
    }

    public function getSortedColumns($useDefault = true) {
        $sortedColumns = [];

        foreach($this->getColumns() as $column) {
            if(!$column instanceof SortableColumnInterface || !$column->isSortable()) continue;

            $order = $this->getSortOrderFromQuery($column);
            if($order) {
                $sortedColumns[$column->getSortExpression()] = $order;
            }
        }

        if(!$sortedColumns && $useDefault) return $this->getDefaultSortColumns();
        return $sortedColumns;
    }

    public function isDefaultSort() {
        return $this->getSortedColumns(false) != $this->getSortedColumns();
    }

    public function updateSortOrder(SortableColumnInterface $column, $order = null) {

    }
}
