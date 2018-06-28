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

use Doctrine\ORM\Query\Expr;
use UnexpectedValueException;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;

/**
 * @author Nicolo Singer <nicolo@whatwedo.ch>
 */
class FilterEventListener
{
    /**
     * @var DoctrineTable $table
     */
    protected $table;

    public function filterResultSet(DataLoadEvent $event)
    {
        $this->table = $event->getTable();

        if (!$this->table instanceof DoctrineTable) {
            // we're only able to filter DoctrineTable
            return;
        }

        if (!$this->table->hasExtension(FilterExtension::class)) {
            return;
        }

        $this->addQueryBuilderFilter();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function queryBuilder()
    {
        return $this->table->getQueryBuilder();
    }

    private function addQueryBuilderFilter()
    {
        $addedJoins = $this->queryBuilder()->getAllAliases();

        $orX = $this->queryBuilder()->expr()->orX();

        $queryFilterColumn = $this->table->getFilterExtension()->getFilterColumns();
        $queryFilterOperator = $this->table->getFilterExtension()->getFilterOperators();
        $queryFilterValue = $this->table->getFilterExtension()->getFilterValues();

        // First, loop all OR's
        foreach ($queryFilterColumn as $orKey => $columns) {
            // Then, loop all AND's
            $andX = $this->queryBuilder()->expr()->andX();
            foreach ($columns as $andKey => $column) {
                if (!isset($this->table->getFilterExtension()->getFilters()[$column])
                    || !isset($queryFilterOperator[$orKey][$andKey])
                    || !isset($queryFilterValue[$orKey][$andKey])) {
                    continue;
                }


                $filter = $this->table->getFilterExtension()->getFilters()[$column];

                foreach ($filter->getType()->getJoins() as $joinAlias => $join) {
                    if (in_array($joinAlias, $addedJoins)) {
                        continue;
                    }
                    $addedJoins[] = $joinAlias;
                    $method = 'join';
                    if (is_array($join)) {
                        $method = $join[0];
                        $join = $join[1];
                    }
                    $this->queryBuilder()->$method($join, $joinAlias);
                }

                $w = $filter->getType()->addToQueryBuilder(
                    $queryFilterOperator[$orKey][$andKey],
                    $queryFilterValue[$orKey][$andKey],
                    implode('_', ['filter', (int)$orKey, (int)$andKey, $filter->getAcronym()]),
                    $this->queryBuilder()
                );
                // $w instanceof Expr does not work. (No extending in doctrine classes.)
                if ((is_object($w) && substr(get_class($w), 0, strlen(Expr::class)) === Expr::class)
                    || is_string($w)) {
                    $andX->add($w);
                } elseif (!is_bool($w)) {
                    $classExpr = Expr::class;
                    throw new UnexpectedValueException("Bool or $classExpr expected as filter-result");
                }
            }


            if (count($andX->getParts()) > 1) {
                $orX->add($andX);
            } elseif (count($andX->getParts()) === 1) {
                $orX->add($andX->getParts()[0]);
            }
        }

        if (count($orX->getParts()) > 1) {
            $this->queryBuilder()->andWhere($orX);
        } elseif (count($orX->getParts()) === 1) {
            $this->queryBuilder()->andWhere($orX->getParts()[0]);
        }

    }

}
