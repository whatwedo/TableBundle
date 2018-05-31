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
     * @var bool $first
     */
    private $first = true;

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
        $this->first = true;
        $this->process();
    }

    /**
     *
     */
    private function process()
    {
        foreach($this->table->getSortedColumns() as $column => $order) {
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
        $alias = count($this->queryBuilder->getRootAliases()) > 0 ? $this->queryBuilder->getRootAliases()[0] : '';

        $sortExp = strpos($sortExp, '.') !== false
            ? $sortExp
            : sprintf('%s.%s', $alias, $sortExp);

        $addOrderByFunction = $this->first ? 'orderBy' : 'addOrderBy';

        $this->first = false;

        if (!in_array(explode('.', $sortExp)[0], $this->queryBuilder->getAllAliases())) {
            $notFound = explode('.', $sortExp)[0];
            throw new \Exception(sprintf('"%s" is not defined in querybuilder. Please override getQueryBuilder in your definition and add a join. For example: %s%s ->leftJoin(sprintf(\'%%s.%s\', self::getQueryAlias()), \'%s\')', $notFound, PHP_EOL, PHP_EOL, $notFound, $notFound));
        }

        $this->queryBuilder->$addOrderByFunction(
            $sortExp,
            $order
        );
    }
}
