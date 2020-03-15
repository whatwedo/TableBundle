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
use InvalidArgumentException;
use UnexpectedValueException;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Table\DoctrineTable;

/**
 * @author Nicolo Singer <nicolo@whatwedo.ch>
 */
class FilterEventListener
{
    /**
     * @var DoctrineTable
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

        $filterExtension = $this->table->getFilterExtension();

        $filterData = $filterExtension->getFilterData();

        // First, loop all OR's
        foreach ($filterData as $groupIndex => $columns) {
            // Then, loop all AND's
            $andX = $this->queryBuilder()->expr()->andX();

            $filterIndex = 0;
            foreach ($columns as $column => $data) {
                ++$filterIndex;

                if (!isset($filterExtension->getFilters()[$column])) {
                    continue;
                }

                $filter = $filterExtension->getFilters()[$column];

                // TODO: automatically join (split field on '.')
                foreach ($filter->getType()->getJoins() as $joinAlias => $join) {
                    if (\in_array($joinAlias, $addedJoins, true)) {
                        continue;
                    }
                    $addedJoins[] = $joinAlias;
                    $method = 'join';
                    $conditionType = null;
                    $condition = null;
                    if (\is_array($join)) {
                        if (4 === count($join)) {
                            $conditionType = $join[2];
                            $condition = $join[3];
                        } elseif (2 !== count($join)) {
                            throw new InvalidArgumentException(sprintf('Invalid join options supplied for "%s".', $joinAlias));
                        }
                        $method = $join[0];
                        $join = $join[1];
                    }
                    $this->queryBuilder()->$method($join, $joinAlias, $conditionType, $condition);
                }

                $w = $filter->getType()->addToQueryBuilder(
                    $data['operator'],
                    $data['value'],
                    implode('_', ['filter', (int) $groupIndex, $filterIndex, $filter->getAcronym()]),
                    $this->queryBuilder()
                );

                if ($w instanceof Expr\Base || $w instanceof Expr\Comparison) {
                    $andX->add($w);
                } elseif (!\is_bool($w)) {
                    throw new UnexpectedValueException(sprintf('Bool or %s expected as filter-result, got %s', Expr::class, \get_class($w)));
                }
            }

            if (\count($andX->getParts()) > 1) {
                $orX->add($andX);
            } elseif (1 === \count($andX->getParts())) {
                $orX->add($andX->getParts()[0]);
            }
        }

        if (\count($orX->getParts()) > 1) {
            $this->queryBuilder()->andWhere($orX);
        } elseif (1 === \count($orX->getParts())) {
            $this->queryBuilder()->andWhere($orX->getParts()[0]);
        }
    }
}
