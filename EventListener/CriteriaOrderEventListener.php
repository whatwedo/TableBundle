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

namespace whatwedo\TableBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\TableBundle\Event\CriteriaLoadEvent;
use whatwedo\TableBundle\Table\AbstractColumn;
use whatwedo\TableBundle\Table\SortableColumnInterface;
use whatwedo\TableBundle\Table\Table;

/**
 * Class CriteriaOrderEventListener
 * @package whatwedo\TableBundle\EventListener
 */
class CriteriaOrderEventListener
{

    /**
     * @var RelationContent $relationContent
     */
    protected $relationContent;

    /**
     * @var Table $table
     */
    protected $table;

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @param CriteriaLoadEvent $event
     */
    public function orderResultSet(CriteriaLoadEvent $event)
    {
        $this->relationContent = $event->getRelationContent();
        $this->table = $event->getTable();
        $this->request = $this->relationContent->getRequest();
        $this->process();
    }

    /**
     *
     */
    protected function process()
    {
        $columns = array_filter($this->table->getColumns()->toArray(), function ($column) {
            return $this->sortThisColumn($column);
        });
        array_walk($columns, function ($column) {
            $this->addOrderBy($column);
        });
    }

    /**
     * @param SortableColumnInterface $column
     */
    protected function addOrderBy(SortableColumnInterface $column)
    {
        $criteria = $this->relationContent->getCriteria();
        $sortExp = $column->getSortExpression();

        $criteria->orderBy(array_merge([
            $sortExp => $this->request->query->has($column->getOrderAscQueryParameter())
                && $this->request->query->get($column->getOrderAscQueryParameter()) == '0' ? Criteria::DESC : Criteria::ASC
        ], $criteria->getOrderings()));


    }

    /**
     * @param AbstractColumn $column
     * @return boolean
     */
    protected function sortThisColumn(AbstractColumn $column)
    {
        return ($column instanceof SortableColumnInterface
            && $column->isSortable()
            && $this->request->query->has($column->getOrderEnabledQueryParameter())
            && $this->request->query->get($column->getOrderEnabledQueryParameter()) === '1');
    }

}
