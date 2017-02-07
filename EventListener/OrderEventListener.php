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

namespace whatwedo\TableBundle\EventListener;


use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Table\AbstractColumn;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\SortableColumnInterface;
use whatwedo\TableBundle\Table\Table;

/**
 * @author Nicolo Singer <nicolo@whatwedo.ch>
 */
class OrderEventListener
{

    /** @var  Table $table */
    private $table;
    /** @var  Request $request */
    private $request;
    /** @var  QueryBuilder $queryBuilder */
    private $queryBuilder;
    /** @var bool $first */
    private $first = true;

    /**
     * @param DataLoadEvent $event
     */
    public function orderResultSet(DataLoadEvent $event)
    {
        $this->table = $event->getTable();
        $this->request = $this->table->getRequest();
        $this->queryBuilder = $this->table->getQueryBuilder();
        $this->process();
    }

    private function process()
    {
        $columns = array_filter($this->table->getColumns()->toArray(), function ($column) {
            return $this->sortThisColumn($column);
        });
        array_walk($columns, function ($column) {
            $this->addOrderBy($column);
        });
    }

    private function addOrderBy(SortableColumnInterface $column)
    {
        $alias = count($this->queryBuilder->getRootAliases()) > 0 ? $this->queryBuilder->getRootAliases()[0] : '';
        $sortExp = strpos($column->getSortExpression(), '.') !== false
            ? $column->getSortExpression()
            : sprintf('%s.%s', $alias, $column->getSortExpression());
        $addOrderByFunction = $this->first ? 'orderBy' : 'addOrderBy';
        $this->first = false;
        if (!in_array(explode('.', $sortExp)[0], $this->queryBuilder->getAllAliases())) {
            $notFound = explode('.', $sortExp)[0];
            throw new \Exception(sprintf('"%s" is not defined in querybuilder. Please override getQueryBuilder in your definition and add a join. For example: %s%s ->leftJoin(sprintf(\'%%s.%s\', self::getQueryAlias()), \'%s\')', $notFound, PHP_EOL, PHP_EOL, $notFound, $notFound));
        }
        $this->queryBuilder->$addOrderByFunction(
            $sortExp,
            $this->request->query->has(SortableColumnInterface::ORDER_ASC . $column->getAcronym())
                && $this->request->query->get(SortableColumnInterface::ORDER_ASC . $column->getAcronym()) == '0' ? 'DESC' : 'ASC'
        );
    }

    private function sortThisColumn(AbstractColumn $column)
    {
        return ($column->isSortableColumn()
            && $column->isSortable()
            && $this->request->query->has(Column::ORDER_ENABLED . $column->getAcronym())
            && $this->request->query->get(Column::ORDER_ENABLED . $column->getAcronym()) === '1');
    }

}
