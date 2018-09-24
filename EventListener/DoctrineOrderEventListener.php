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
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\SortableColumnInterface;

/**
 * Class DoctrineOrderEventListener
 * @package whatwedo\TableBundle\EventListener
 */
class DoctrineOrderEventListener
{

    /**
     * @var DoctrineTable $table
     */
    private $table;

    /**
     * @var QueryBuilder $queryBuilder
     */
    private $queryBuilder;

    /**
     * @param DataLoadEvent $event
     */
    public function orderResultSet(DataLoadEvent $event)
    {
        if (!$event->getTable() instanceof DoctrineTable) {
            return;
        }

        $this->table = $event->getTable();
        $this->queryBuilder = $this->table->getQueryBuilder();
        $this->process();
    }

    /**
     *
     */
    private function process()
    {
        $sortedColumns = $this->table->getSortedColumns();
        if(!empty($sortedColumns)) $this->queryBuilder->resetDQLPart('orderBy');

        foreach($sortedColumns as $column => $order) {
            foreach(explode(',', $column) as $sortExp) {
                $this->addOrderBy($sortExp, $order);
            }
        }
    }

    /**
     * @param string $sortExp
     * @param string $order
     * @throws \Exception
     */
    private function addOrderBy(string $sortExp, string $order) {
        $alias = $this->queryBuilder->getRootAliases()[0];

        if(strpos($sortExp, '.') === false) {
            $sortExp = sprintf('%s.%s', $alias, $sortExp);
        }

        $allAliases = $this->queryBuilder->getAllAliases();

        $sortAliases = array_slice(explode('.', $sortExp), 0, -1);

        foreach($sortAliases as $sortAlias) {
            if(!in_array($sortAlias, $allAliases)) {
                $this->queryBuilder->leftJoin(sprintf('%s.%s', $alias, $sortAlias), $sortAlias);
            }

            $alias = $sortAlias;
        }

        $sortExp = implode('.', array_slice(explode('.', $sortExp), -2));

        $this->queryBuilder->addOrderBy(
            $sortExp,
            $order
        );
    }
}
